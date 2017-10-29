<?php

namespace App\Controller;

use App\Model\Catalog;
use App\Model\Color;

class Catalogs
{
    public function index($request, $response)
    {
        return $response->withJson(array(
            'products' => Catalog::with('colors')->get()
        ));
    }

    public function create($request, $response)
    {
        $params = $request->getParsedBody();
        $catalog = new Catalog();
        $catalog->fulfiller_code = $params['fulfiller_code'];
        $catalog->name = $params['name'];
        $catalog->save();

        foreach ($params['colors'] as $color) {
            $c = new Color();
            $c->name = $color['name'];
            $c->alias = isset($color['alias']) ? $color['alias'] : null;
            $c->catalog_id = $catalog->fulfiller_code;
            $c->save();
        }

        return $response->withJson(array(
            'success' => true
        ));
    }

    public function show($request, $response, $arguments)
    {
        $catalog = Catalog::where('fulfiller_code', $arguments['catalogId'])->with('colors')->first();
        if (!$catalog) {
            return $response->withStatus(404);
        }
        return $response->withJson(array(
            'product' => $catalog
        ));
    }

    public function update($request, $response, $arguments)
    {
        $params = $request->getParsedBody();
        $catalog = Catalog::where('fulfiller_code', $arguments['catalogId'])->first();
        if (!$catalog) {
            return $response->withStatus(404);
        }
        $catalog->name = $params['name'];
        
        return $response->withJson(array(
            'success' => true
        ))
    }

    public function delete($request, $response, $arguments)
    {
        Catalog::destroy($arguments['catalogId']);
        Color::where('catalog_id', $arguments['catalogId'])->delete();
        return $response->withJson(array(
            'success' => true
        ));
    }

    public function add_color($request, $response, $arguments)
    {
        $params = $request->getParsedBody();
        $color = Color::where('fulfiller_code', $arguments['catalogId'])
            ->where('name', $params['name'])
            ->first();
        if ($color) {
            return $response->withStatus(400)->withJson(array(
                'error' => "A color with that name already exists for this product"
            ));
        }
        $c = new Color();
        $c->catalog_id = $arguments['catalogId'];
        $c->name = $params['name'];
        $c->alias = isset($params['alias']) ? $params['alias'] : null;
        $c->save();

        return $response->withJson(array(
            'success' => true
        ));
    }

    public function update_color($request, $response, $arguments)
    {
        $params = $request->getParsedBody();
        Color::where('id', $arguments['colorId'])
            ->update([
                'name' => $params['name'],
                'alias' => $params['alias']
            ]);
        return $response->withJson(array(
            'success' => true
        ));
    }

    public function remove_color($request, $response, $arguments)
    {
        Color::destroy($arguments['colorId']);
        return $response->withJson(array(
            'success' => true
        ));
    }
}
