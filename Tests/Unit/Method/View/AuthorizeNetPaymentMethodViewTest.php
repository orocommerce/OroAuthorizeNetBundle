<?php

namespace Oro\Bundle\AuthorizeNetBundle\Tests\Unit\Method\View;

use Oro\Bundle\AuthorizeNetBundle\Form\Type\CheckoutCredicardProfileType;
use Oro\Bundle\AuthorizeNetBundle\Form\Type\CreditCardType;
use Oro\Bundle\AuthorizeNetBundle\Method\Config\AuthorizeNetConfigInterface;
use Oro\Bundle\AuthorizeNetBundle\Method\View\AuthorizeNetPaymentMethodView;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class AuthorizeNetPaymentMethodViewTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    const ALLOWED_CC_TYPES = ['visa', 'mastercard'];

    /** @var FormFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $formFactory;

    /** @var AuthorizeNetPaymentMethodView */
    protected $methodView;

    /** @var AuthorizeNetConfigInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $paymentConfig;

    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $tokenAccessor;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->paymentConfig = $this->createMock(AuthorizeNetConfigInterface::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);

        $this->methodView = new AuthorizeNetPaymentMethodView(
            $this->formFactory,
            $this->tokenAccessor,
            $this->paymentConfig
        );
    }

    /**
     * @param bool $requireCvvEntryEnabled
     * @param bool $isEnabledCIM
     * @dataProvider getOptionsProvider
     */
    public function testGetOptions($requireCvvEntryEnabled, $isEnabledCIM)
    {
        [$formView, $context] = $this->prepareMocks($requireCvvEntryEnabled, $isEnabledCIM);

        $this->assertEquals(
            [
                'formView' => $formView,
                'paymentMethodComponentOptions' => [
                    'allowedCreditCards' => self::ALLOWED_CC_TYPES,
                    'clientKey' => 'client key',
                    'apiLoginID' => 'api login id',
                    'testMode' => true,
                ],
            ],
            $this->methodView->getOptions($context)
        );
    }

    public function testGetPaymentMethodIdentifier()
    {
        $this->paymentConfig->expects($this->once())
            ->method('getPaymentMethodIdentifier')
            ->willReturn('identifier');

        $this->assertEquals('identifier', $this->methodView->getPaymentMethodIdentifier());
    }

    public function testGetLabel()
    {
        $this->paymentConfig->expects($this->once())
            ->method('getLabel')
            ->willReturn('label');

        $this->assertEquals('label', $this->methodView->getLabel());
    }

    public function testGetShortLabel()
    {
        $this->paymentConfig->expects($this->once())
            ->method('getShortLabel')
            ->willReturn('short label');

        $this->assertEquals('short label', $this->methodView->getShortLabel());
    }

    public function testGetBlock()
    {
        $this->assertEquals('_payment_methods_authorize_net_widget', $this->methodView->getBlock());
    }

    /**
     * @param bool $requireCvvEntryEnabled
     * @param bool $isEnabledCIM
     * @return array|\PHPUnit\Framework\MockObject\MockObject[]
     */
    protected function prepareMocks($requireCvvEntryEnabled, $isEnabledCIM)
    {
        $formView = $this->createMock(FormView::class);
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())->method('createView')->willReturn($formView);

        $this->tokenAccessor
            ->expects($this->once())
            ->method('hasUser')
            ->willReturn(true);

        $formOptions = [
            'requireCvvEntryEnabled' => $requireCvvEntryEnabled,
            'allowedCreditCards' => self::ALLOWED_CC_TYPES
        ];
        $formClass = CreditCardType::class;

        if ($isEnabledCIM) {
            $formClass = CheckoutCredicardProfileType::class;
        }

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with($formClass, null, $formOptions)
            ->willReturn($form);

        $this->paymentConfig->expects($this->once())
            ->method('getAllowedCreditCards')
            ->willReturn(self::ALLOWED_CC_TYPES);

        $this->paymentConfig->expects($this->once())
            ->method('getApiLoginId')
            ->willReturn('api login id');

        $this->paymentConfig->expects($this->once())
            ->method('getClientKey')
            ->willReturn('client key');

        $this->paymentConfig->expects($this->once())
            ->method('isTestMode')
            ->willReturn(true);

        $this->paymentConfig->expects($this->once())
            ->method('isEnabledCIM')
            ->willReturn($isEnabledCIM);

        $this->paymentConfig->expects($this->once())
            ->method('isRequireCvvEntryEnabled')
            ->willReturn($requireCvvEntryEnabled);

        /** @var PaymentContextInterface|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(PaymentContextInterface::class);

        return array($formView, $context);
    }

    /**
     * @return array
     */
    public function getOptionsProvider()
    {
        return [
            'cvv not required, cim disabled' => [
                'requireCvvEntryEnabled' => false,
                'isEnabledCIM' => false
            ],
            'cvv required, cim disabled' => [
                'requireCvvEntryEnabled' => true,
                'isEnabledCIM' => false
            ],
            'cvv required, cim enabled' => [
                'requireCvvEntryEnabled' => true,
                'isEnabledCIM' => true
            ],
            'cvv not required, cim enabled' => [
                'requireCvvEntryEnabled' => false,
                'isEnabledCIM' => true
            ]
        ];
    }
}
