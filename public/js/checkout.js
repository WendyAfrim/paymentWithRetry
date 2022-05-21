let checkout = (() => {
    let self = {};

    self.init = () => {
        const stripe = self.instanciateStripeObject();
        self.createPaymentIntent(stripe);
        // self.submitForm(stripe);
        // self.retrievePaymentIntent();
    };

    self.instanciateStripeObject = () => {

        return Stripe(TWIG.publicKey);
    };

    self.createPaymentIntent = (stripe) => {

        // Fetches a payment intent and captures the client secret
        async function initialize() {
            // const response = await fetch("/create-payment-intent", {
            //     method: "POST",
            //     headers: { "Content-Type": "application/json" },
            //     body: null,
            // });
            console.log('hello');

            const response = await fetch('/create-payment-intent');
            const clientSecret = await response.json();

            const appearance = {
                theme: 'stripe',
            };
            elements = stripe.elements({ appearance, clientSecret });
            console.log(elements);

            const paymentElement = elements.create("payment");
            paymentElement.mount("#payment-element");
        }

        initialize();
    };


    self.submitForm = (stripe) => {
        const form = document.getElementById('payment-form');

        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const {error} = await stripe.confirmPayment({
                //`Elements` instance that was used to create the Payment Element
                elements,
                confirmParams: {
                    return_url: 'https://example.com/order/123/complete',
                },
            });

            if (error) {
                // This point will only be reached if there is an immediate error when
                // confirming the payment. Show error to your customer (for example, payment
                // details incomplete)
                const messageContainer = document.querySelector('#error-message');
                messageContainer.textContent = error.message;
            } else {
                // Your customer will be redirected to your `return_url`. For some payment
                // methods like iDEAL, your customer will be redirected to an intermediate
                // site first to authorize the payment, then redirected to the `return_url`.
            }
        });
    };

    self.retrievePaymentIntent = () => {
        // Initialize Stripe.js using your publishable key
        const stripe = Stripe('pk_test_oKhSR5nslBRnBZpjO6KuzZeX');

        // Retrieve the "payment_intent_client_secret" query parameter appended to
        // your return_url by Stripe.js
        const clientSecret = new URLSearchParams(window.location.search).get(
            'payment_intent_client_secret'
        );

        // Retrieve the PaymentIntent
        stripe.retrievePaymentIntent(clientSecret).then(({paymentIntent}) => {
            const message = document.querySelector('#message')

            // Inspect the PaymentIntent `status` to indicate the status of the payment
            // to your customer.
            //
            // Some payment methods will [immediately succeed or fail][0] upon
            // confirmation, while others will first enter a `processing` state.
            //
            // [0]: https://stripe.com/docs/payments/payment-methods#payment-notification
            switch (paymentIntent.status) {
                case 'succeeded':
                    message.innerText = 'Success! Payment received.';
                    break;

                case 'processing':
                    message.innerText = "Payment processing. We'll update you when payment is received.";
                    break;

                case 'requires_payment_method':
                    message.innerText = 'Payment failed. Please try another payment method.';
                    // Redirect your user back to your payment page to attempt collecting
                    // payment again
                    break;

                default:
                    message.innerText = 'Something went wrong.';
                    break;
            }
        });
    }
    return self;
})();


$(document).ready(function(){
    checkout.init();
})