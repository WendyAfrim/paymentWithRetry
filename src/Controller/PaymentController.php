<?php

namespace App\Controller;

use Stripe\Stripe;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PaymentController extends AbstractController
{
    const PAYMENT_KEY = 'st_payment';
    const REDIS_URL = 'redis://127.0.0.1:53020';

    /**
     * @Route("/payment", name="app_payment")
     */
    public function index(): Response
    {
        $publicKey = $_ENV['STRIPE_PUBLIC_KEY'];
        $secretKey = $_ENV['STRIPE_SECRET'];

        return $this->render('payment/index.html.twig', [
            'publicKey' => $publicKey,
            'secretKey' => $secretKey
        ]);
    }

    /**
     * @Route("/checkout", name="app_checkout")
     */
    public function checkout(): Response
    {

        return $this->render('payment/checkout.html.twig');
    }

    /**
     * @Route("/create-payment-intent", name="app_create_payment_intent")
     */
    public function createPaymentIntent() : Response
    {

        $secretKey = $_ENV['STRIPE_SECRET'];
        $stripe = new \Stripe\StripeClient($secretKey);

        $paymentMethod = $stripe->paymentMethods->create([
            'type' => 'card',
            'card' => [
                'number' => '4242424242424242',
                'exp_month' => 2,
                'exp_year' => 42,
                'cvc' => '424',
            ],
        ]);

        $response = $stripe->paymentIntents->create(
            [
                'amount' => 1099,
                'currency' => 'eur',
                'automatic_payment_methods' => ['enabled' => true],
                "payment_method"=> $paymentMethod,
                'return_url' => 'http://localhost:8001/'.$this->generateUrl('app_checkout'),
                'confirm' => true
            ]
        );
        $response = $this->setPaymentInRedisSession($response);
        dd($response);

        return new Response(
            json_encode($response->client_secret),
            Response::HTTP_OK,
            ['content-type' => 'application/json']
        );
    }

    public function getRedisConnection()
    {
        return RedisAdapter::createConnection(self::REDIS_URL);
    }

    public function setPaymentInRedisSession ($response)
    {
        $redisCache = $this->getRedisConnection();

        if (!$redisCache->get(self::PAYMENT_KEY)) {
            $redisCache->set(self::PAYMENT_KEY.'_'.$response->id, $response);
        }

        return new Response(
            'The payment has been proceed',
            Response::HTTP_OK,
            ['content-type' => 'application/json']
        );
    }
}
