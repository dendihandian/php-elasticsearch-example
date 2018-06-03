<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Elasticsearch\ClientBuilder;
use Illuminate\Http\Request;

class ProductsController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->elasticsearch = ClientBuilder::create()->build();
    }

    public function index()
    {
        $products = Product::all();
        return response()->json($products, 200);
    }

    public function show($id)
    {
        $params = [
          'index' => Product::INDEX,
          'type' => 'default',
          'id' => $id
        ];

        $response = $this->elasticsearch->get($params);

        return response()->json($response['_source'], 200);
    }

    public function store(Request $request)
    {
        $input = $request->all();

        $product = new Product;
        $product->name = $input['name'];
        $product->slug = str_slug($input['name']);
        $product->stock = $input['stock'];
        $product->price = $input['price'];
        $product->description = $input['description'];
        $product->save();

        // create new index
        $params = [
          'index' => Product::INDEX,
          'type' => 'default',
          'id' => $product->id,
          'body' => $product->toArray(),
        ];

        $response = $elasticsearch->index($params);

        return response()->json($product, 201);
    }

    public function update(Request $request, $id)
    {
        $input = $request->all();

        $product = Product::findOrFail($id);
        $product->name = $input['name'];
        $product->slug = str_slug($input['name']);
        $product->stock = $input['stock'];
        $product->price = $input['price'];
        $product->description = $input['description'];
        $product->save();

        // delete old index
        $params = [
          'index' => Product::INDEX,
          'type' => 'default',
          'id' => $product->id,
        ];

        $response = $elasticsearch->delete($params);

        // create new index
        $params = [
          'index' => Product::INDEX,
          'type' => 'default',
          'id' => $product->id,
          'body' => $product->toArray(),
        ];

        $response = $elasticsearch->index($params);

        return response()->json($product, 200);
    }

    public function destroy($id)
    {
        // get product from id
        $product = Product::findOrFail($id);

        // delete index
        $params = [
          'index' => Product::INDEX,
          'type' => 'default',
          'id' => $product->id,
        ];

        $response = $elasticsearch->delete($params);

        // delete product
        $product->delete();

        return response()->json([], 200);
    }
}
