<?php

namespace App\Controller;

use App\Model\User;
use App\Model\Shop;
use App\Model\Errors;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Flash\Messages;
use Slim\Views\Twig;

class Users
{
    /**
     * @var Twig
     */
    protected $view;

    /**
     * @var Messages
     */
    protected $flash;

    public function __construct(Twig $view, Messages $flash)
    {
        $this->view = $view;
        $this->flash = $flash;
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response, array $arguments = [])
    {
        $users = User::all();
        return $this->view->render($response, 'users/index.html', array(
            'users' => $users
        ));
    }

    public function show(ServerRequestInterface $request, ResponseInterface $response, array $arguments = [])
    {
        $uid = $arguments['id'];
        $user = User::find($uid);
        if (empty($user)) {
            $this->flash->addMessage('error', "We couldnt find user with ID of {$uid}");
            return $response->withRedirect('/users');
        }

        $this->view->render($response, 'users/show.html', array(
            'user' => $user
        ));
    }

    public function create(ServerRequestInterface $request, ResponseInterface $response, array $arguments = [])
    {
        if ($request->getAttribute('user')->role != 'admin') {
            $this->flash->addMessage('error', Errors::UNAUTHORIZED);
            return $response->withRedirect('/users');
        }

        if ($request->isGet()) {
            return $this->view->render($response, 'users/new.html');
        }

        $params = $request->getParsedBody();

        if ($params['password'] !== $params['confirm']) {
            $this->flash->addMessage('error', "Passwords did not match!");
            return $response->withRedirect('/users/create');
        }

        $user = new User();
        $user->email = $params['email'];
        $user->role = $params['role'];
        $user->password = $params['password'];

        try {
            $user->save();
        } catch (\Exception $e) {
            $this->flash->addMessage('error', $e->getMessage());
            return $response->withRedirect('/users/create');
        }

        $this->flash->addMessage('message', 'User successfully created');
        return $response->withRedirect("/users/{$user->id}");
    }

    public function update(ServerRequestInterface $request, ResponseInterface $response, array $arguments = [])
    {
        if ($request->getAttribute('user')->role != 'admin') {
            $this->flash->addMessage('error', Errors::UNAUTHORIZED);
            return $response->withRedirect('/users');
        }
        $user = User::find($arguments['id']);
        if (empty($user)) {
            return $this->view->render($response, 'users/index.html', array(
                'error' => "We couldn't find that user"
            ));
        }

        $params = $request->getParsedBody();
        if ($params['new_pass'] != '') {
            if ($params['new_pass'] !== $params['confirm']) {
                $this->flash->addMessage('error', "Provided passwords did not match");
                return $response->withRedirect("/users/{$arguments['id']}");
            }
            $user->password = $params['new_pass'];
        }
        $user->email = $params['email'];
        $user->role = $params['role'];
        try {
            $user->update();
        } catch (\Exception $e) {
            $this->flash->addMessage('error', $e->getMessage());
            return $response->withRedirect("/users/{$arguments['id']}");
        }

        $this->flash->addMessage('message', "User successfully updated");
        return $response->withRedirect("/users/{$arguments['id']}");
    }

    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $arguments = [])
    {
        if ($request->getAttribute('user')->role != 'admin') {
            $this->flash->addMessage('error', Errors::UNAUTHORIZED);
            return $response->withRedirect('/users');
        }

        $user = User::find($arguments['id']);
        if (empty($user)) {
            $this->flash->addMessage('error', "User {$arguments['id']} not found");
            return $response->withRedirect('/users');
        }

        if ($request->isGet()) {
            return $this->view->render($response, 'users/confirm.html', array(
                'user' => $user
            ));
        } else {
            $user->delete();
            $this->flash->addMessage('message', 'User succesfully deleted');
            return $response->withRedirect('/users');
        }

    }

    public function access(ServerRequestInterface $request, ResponseInterface $response, array $arguments = [])
    {
        if ($request->getAttribute('user')->role != 'admin') {
            $this->flash->addMessage('error', Errors::UNAUTHORIZED);
            return $response->withRedirect('/users');
        }

        $params = $request->getParsedBody();
        $user = User::with('shops')->find($arguments['id']);
        if (empty($user)) {
            $this->flash->addMessage('error', "User not found");
            return $response->withRedirect('/users');
        }
        if ($request->isGet()) {
            $allShops = Shop::all();
            $shops = $user->shops;
            foreach ($shops as $shop) {
                foreach ($allShops as $index => $allShop) {
                    if ($shop->id == $allShop->id) {
                        $allShops[$index]->userHasAccess = true;
                    }
                }
            }

            return $this->view->render($response, 'users/access.html', array(
                'user' => $user,
                'shops' => $allShops
            ));
        } else {
            $params = $request->getParsedBody();
            $ids = array();
            foreach ($params as $key => $value) {
                if (substr($key, 0, 5) == 'shop_') {
                    $shopId = substr($key, 5);
                    if ($value == 'on') {
                        $ids[] = $shopId;
                    }
                }
            }
            $shops = Shop::all();
            foreach ($shops as $shop) {
                if (in_array($shop->id, $ids)) {
                    if (!$user->shops->contains($shop)) {
                        $user->shops()->attach($shop->id);
                    }
                } else {
                    error_log("Detaching {$shop->id}");
                    $user->shops()->detach($shop->id);
                }
            }

            $this->flash->addMessage('message', "Access succesfully updated");
            return $response->withRedirect("/users/{$arguments['id']}/access");
        }
    }

    public function settings(ServerRequestInterface $request, ResponseInterface $response, array $arguments = [])
    {
        $user = User::find($arguments['id']);
        if ($request->isPost()) {
            $body = $request->getParsedBody();
            $user->default_shops = $body['default_shop'];
            $user->save();
            $this->flash->addMessage('message', "Default shops succesfully updated");
            return $response->withRedirect("/users/{$arguments['id']}/settings");
        }
        return $this->view->render($response, 'users/settings.html', array(
            'user' => $user,
            'shops' => Shop::all()
        ));
    }
}
