<?php

namespace App\Repositories;

use App\Models\Order;
use App\Models\Product;
use App\Models\OrderItem;
Use App\Controller\AddToCart;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Doctrine\Instantiator\Exception\InvalidArgumentException;

class OrderRepository extends BaseRepository implements OrderContract
{
    public function __construct(Order $model)
    {
        parent::__construct($model);
        $this->model = $model;
    }

    public function storeOrderDetails($params)
    {
        $order = Order::create([
            'order_number'      =>  'ORD-'.strtoupper(uniqid()),
            'user_id'           => auth()->user()->id,
            'status'            =>  'pending',
            'grand_total'       =>  AddToCart::getSubTotal(),
            'item_count'        =>  AddToCart::getTotalQuantity(),
            'payment_status'    =>  0,
            'payment_method'    =>  null,
            'first_name'        =>  $params['first_name'],
            'last_name'         =>  $params['last_name'],
            'address'           =>  $params['address'],
            'state'              =>  $params['city'],
            'district'           =>  $params['district'],
            'post_code'         =>  $params['postcode'],
            'phone_number'      =>  $params['phonenumber'],
            'notes'             =>  $params['notes']
        ]);

        if ($order) {

            $items = Cart::getContent();

            foreach ($items as $item)
            {
                // A better way will be to bring the product id with the cart items
                // you can explore the package documentation to send product id with the cart
                $product = Product::where('name', $item->name)->first();



                $orderItem = new OrderItem([
                    'product_id'    =>  $product->id,
                    'quantity'      =>  $item->quantity,
                    'price'         =>  $item->getPriceSum()
                ]);
                $product=Product::where('id', '=', $orderItem->product_id)->decrement('quantity',$item->quantity);
                $order->items()->save($orderItem);

            }
        }

        return $order;
    }

    public function listOrders(string $order = 'id', string $sort = 'desc', array $columns = ['*'])
    {
        return $this->all($columns, $order, $sort);
    }
     /**
     * @param int $id
     * @return mixed
     * @throws ModelNotFoundException
     */
    public function findOrderById(int $id)
    {
        try {
            return $this->findOneOrFail($id);

        } catch (ModelNotFoundException $e) {

            throw new ModelNotFoundException($e);
        }

    }

    public function findOrderByNumber($orderNumber)
    {
        return Order::where('order_number', $orderNumber)->first();
    }
     /**
     * @param array $params
     * @return mixed
     */
    public function updateOrder(array $params)
    {
        $order = $this->findOrderById($params['id']);
        $collection = collect($params)->except('_token');
        $order->update();
        return $order;
    }
     /**
     * @param $id
     * @return bool|mixed
     */
    public function deleteOrder($orderNumber)
    {
        $order = $this->findOrderByNumber($orderNumber);
        $order->delete();
        return $order;
    }
}