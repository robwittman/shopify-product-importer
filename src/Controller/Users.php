<?php

namespace App\Controller;

use App\Model\User;
use App\Model\Shop;
use App\Model\Errors;

class Users
{
    public function index($request, $response, $arguments)
    {
        $users = User::all();
        return $response->withJson(array(
            'users' => $users
        ));
    }

    public function show($request, $response, $arguments)
    {
        $uid = $arguments['id'];
        $user = User::find($uid);
        if (empty($user)) {
            return $response->withStatus(404);
        }

        return $response->withJson(array(
            'user' => $user
        ));
    }

    public function create($request, $response, $arguments)
    {
        $params = $request->getParsedBody();

        $user = new User();
        $user->email = $params['email'];
        $user->role = $params['role'];
        $user->password = $params['password'];

        try {
            $user->save();
        } catch (\Exception $e) {
            return $response->withStatus(400)->withJson(array(
                'error' => $e->getMessage()
            ));
        }

        return $response->withJson(array(
            'id' => $user->id
        ));
    }

    public function update($request, $response, $arguments)
    {
        $user = User::find($arguments['id']);
        if (empty($user)) {
            return $response->withStatus(404);
        }

        $params = $request->getParsedBody();
        if ($params['new_pass'] != '') {
            $user->password = $params['new_pass'];
        }
        $user->email = $params['email'];
        $user->role = $params['role'];
        try {
            $user->update();
        } catch (\Exception $e) {
            return $response->withStatus(400)->withJson(array(
                'error' => $e->getMessage()
            ));
        }

        return $response->withJson(array('success' => true));
    }

    public function delete($request, $response, $arguments)
    {
        $user = User::find($arguments['id']);
        if (empty($user)) {
            return $response->withStatus(404);
        }

        try {
            $user->delete();
        } catch( \Exception $e) {
            return $response->withStatus(400)->withJson(array(
                'error' => $e->getMessage()
            ));
        }

        return $response->withJson(array(
            'success' => true
        ));
    }

    public function access($request, $response, $arguments)
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
}
