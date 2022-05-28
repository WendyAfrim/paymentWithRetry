<?php

namespace App\Controller;

use Psr\Cache\CacheItemPoolInterface;
use Stripe\Stripe;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PaymentController extends AbstractController
{
    const PAYMENT_KEY = 'st_payment';

    private $cacheItemPool;

    public function __construct(CacheItemPoolInterface  $cacheItemPool) {

        $this->cacheItemPool = $cacheItemPool;
    }

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
        $itemPotencyKey = uniqid();
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
            ],
            [
                'idempotency_key' => $itemPotencyKey
            ]
        );

        $clientTransation = [
            'stripe_response' => $response,
            'transaction_id'  => self::PAYMENT_KEY.'_'.$response->id,
            'idem_potency_key' => $itemPotencyKey
        ];

        $response = $this->setIdemPotencyKeyInSession($clientTransation);

        return new Response(
            json_encode($response->client_secret),
            Response::HTTP_OK,
            ['content-type' => 'application/json']
        );
    }

    public function getRedisConnection()
    {
        return $this->cacheItemPool;
    }

    public function setIdemPotencyKeyInSession(array $clientTransaction)
    {
        $redisCache = $this->getRedisConnection();

        $valueFromCache = $redisCache->getItem($clientTransaction['idem_potency_key']);

        if (!$valueFromCache->isHit()) {
            $valueFromCache->set($clientTransaction);
        }

        return new Response(
            'The payment has been proceed',
            Response::HTTP_OK,
            ['content-type' => 'application/json']
        );
    }
}
