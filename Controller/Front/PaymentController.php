<?php

namespace OpenApi\Controller\Front;

use OpenApi\Annotations as OA;
use OpenApi\Model\Api\PaymentModule;
use Symfony\Component\Routing\Annotation\Route;
use Thelia\Core\Event\Payment\IsValidPaymentEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\HttpFoundation\Request;
use Thelia\Model\Cart;
use Thelia\Model\Lang;
use Thelia\Model\Module;
use Thelia\Model\ModuleQuery;
use Thelia\Module\BaseModule;

/**
 * @Route("/payment", name="payment")
 */
class PaymentController extends BaseFrontOpenApiController
{
    /**
     * @Route("/modules", name="payment_modules", methods="GET")
     *
     * @OA\Get(
     *     path="/payment/modules",
     *     tags={"payment", "modules"},
     *     summary="List all available payment modules",
     *     @OA\Parameter(
     *          name="orderId",
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          )
     *     ),
     *     @OA\Parameter(
     *          name="moduleId",
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          )
     *     ),
     *     @OA\Response(
     *          response="200",
     *          description="Success",
     *          @OA\JsonContent(
     *                  type="array",
     *                  @OA\Items(
     *                      ref="#/components/schemas/PaymentModule"
     *                  )
     *          )
     *     ),
     *     @OA\Response(
     *          response="400",
     *          description="Bad request",
     *          @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
     */
    public function getPaymentModules(Request $request)
    {
        $cart = $request->getSession()->getSessionCart($this->getDispatcher());
        $lang = $request->getSession()->getLang();
        $moduleQuery = ModuleQuery::create()
            ->filterByActivate(1)
            ->filterByType(BaseModule::PAYMENT_MODULE_TYPE)
            ->orderByPosition();

        if (null !== $moduleId = $request->get('moduleId')) {
            $moduleQuery->filterById($moduleId);
        }

        $modules = $moduleQuery->find();

        $class = $this;

        // Return formatted valid payment
        return $this->jsonResponse(
            array_map(
                function ($module) use ($class, $cart, $lang) {
                    return $class->getPaymentModule($module, $cart, $lang);
                },
                iterator_to_array($modules)
            )
        );
    }

    protected function getPaymentModule(Module $paymentModule, Cart $cart, Lang $lang)
    {
        $paymentModule->setLocale($lang->getLocale());
        $moduleInstance = $paymentModule->getPaymentModuleInstance($this->container);

        $isValidPaymentEvent = new IsValidPaymentEvent($moduleInstance, $cart);
        $this->getDispatcher()->dispatch(
            TheliaEvents::MODULE_PAYMENT_IS_VALID,
            $isValidPaymentEvent
        );

        /** @var PaymentModule $paymentModule */
        $paymentModule = $this->getModelFactory()->buildModel('PaymentModule', $paymentModule);
        $paymentModule->setValid($isValidPaymentEvent->isValidModule())
            ->setCode($moduleInstance->getCode())
            ->setMinimumAmount($isValidPaymentEvent->getMinimumAmount())
            ->setMaximumAmount($isValidPaymentEvent->getMaximumAmount());

        return $paymentModule;
    }
}
