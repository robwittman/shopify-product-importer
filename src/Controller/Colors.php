<?php

namespace App\Controller;

use App\Model\Color;

class Colors
{
    public function index($request, $response)
    {
        $colors = array_map(function($color) {
            return [
                'id' => $color['id'],
                'name' => $color['name']
            ];
        }, Color::all()->toArray());

        return $response->withJson([
            'results' => $colors
        ]);
    }

    public function search($request, $response)
    {
        $colors = Color::where('name', 'LIKE', "%{$request->getParam('q')}%")->get();
        $colors = array_map(function($color) {
            return [
                'id' => $color['id'],
                'name' => $color['name']
            ];
        }, $colors->toArray());
        return $response->withJson([
            'results' => $colors
        ]);
    }

    public function create($request, $response)
    {
        $params = $request->getParsedBody();
        $color = new Color();
        $color->name = $params['name'];
        $color->save();
        return $response->withJson([
            'success' => true,
            'color' => $color
        ]);
    }
}
