<?php

declare(strict_types=1);

namespace spec\Sylius\InvoicingPlugin\Generator;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use PhpSpec\ObjectBehavior;
use Sylius\Component\Core\Model\AddressInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\InvoicingPlugin\Converter\BillingDataConverterInterface;
use Sylius\InvoicingPlugin\Converter\InvoiceChannelConverterInterface;
use Sylius\InvoicingPlugin\Converter\InvoiceShopBillingDataConverterInterface;
use Sylius\InvoicingPlugin\Converter\LineItemsConverterInterface;
use Sylius\InvoicingPlugin\Converter\TaxItemsConverterInterface;
use Sylius\InvoicingPlugin\Entity\BillingData;
use Sylius\InvoicingPlugin\Entity\InvoiceChannelInterface;
use Sylius\InvoicingPlugin\Entity\InvoiceInterface;
use Sylius\InvoicingPlugin\Entity\InvoiceShopBillingDataInterface;
use Sylius\InvoicingPlugin\Factory\InvoiceFactoryInterface;
use Sylius\InvoicingPlugin\Generator\InvoiceGeneratorInterface;
use Sylius\InvoicingPlugin\Generator\InvoiceIdentifierGenerator;
use Sylius\InvoicingPlugin\Generator\InvoiceNumberGenerator;

final class InvoiceGeneratorSpec extends ObjectBehavior
{
    function let(
        InvoiceIdentifierGenerator $uuidInvoiceIdentifierGenerator,
        InvoiceNumberGenerator $sequentialInvoiceNumberGenerator,
        InvoiceFactoryInterface $invoiceFactory,
        BillingDataConverterInterface $billingDataConverter,
        InvoiceChannelConverterInterface $invoiceChannelConverter,
        InvoiceShopBillingDataConverterInterface $invoiceShopBillingDataConverter,
        LineItemsConverterInterface $lineItemConverter,
        TaxItemsConverterInterface $taxItemsConverter
    ): void {
        $this->beConstructedWith(
            $uuidInvoiceIdentifierGenerator,
            $sequentialInvoiceNumberGenerator,
            $invoiceFactory,
            $billingDataConverter,
            $invoiceChannelConverter,
            $invoiceShopBillingDataConverter,
            $lineItemConverter,
            $taxItemsConverter
        );
    }

    function it_is_an_invoice_generator(): void
    {
        $this->shouldImplement(InvoiceGeneratorInterface::class);
    }

    function it_generates_an_invoice_for_a_given_order(
        InvoiceIdentifierGenerator $uuidInvoiceIdentifierGenerator,
        InvoiceNumberGenerator $sequentialInvoiceNumberGenerator,
        InvoiceFactoryInterface $invoiceFactory,
        BillingDataConverterInterface $billingDataConverter,
        InvoiceChannelConverterInterface $invoiceChannelConverter,
        InvoiceShopBillingDataConverterInterface $invoiceShopBillingDataConverter,
        LineItemsConverterInterface $lineItemConverter,
        TaxItemsConverterInterface $taxItemsConverter,
        OrderInterface $order,
        AddressInterface $billingAddress,
        ChannelInterface $channel,
        InvoiceChannelInterface $invoiceChannel,
        InvoiceShopBillingDataInterface $invoiceShopBillingData,
        BillingData $billingData,
        Collection $lineItems,
        Collection $taxItems,
        InvoiceInterface $invoice
    ): void {
        $date = new \DateTimeImmutable('2019-03-06');

        $uuidInvoiceIdentifierGenerator->generate()->willReturn('7903c83a-4c5e-4bcf-81d8-9dc304c6a353');
        $sequentialInvoiceNumberGenerator->generate()->willReturn($date->format('Y/m') . '/0000001');

        $order->getNumber()->willReturn('007');
        $order->getCurrencyCode()->willReturn('USD');
        $order->getLocaleCode()->willReturn('en_US');
        $order->getTotal()->willReturn(10300);
        $order->getChannel()->willReturn($channel);
        $order->getBillingAddress()->willReturn($billingAddress);

        $mockedLineItems = new ArrayCollection();
        $mockedTaxItems = new ArrayCollection();

        $billingDataConverter->convert($billingAddress)->willReturn($billingData);
        $invoiceChannelConverter->convert($channel)->willReturn($invoiceChannel);
        $invoiceShopBillingDataConverter->convert($channel)->willReturn($invoiceShopBillingData);
        $lineItemConverter->convert($order)->willReturn($mockedLineItems);
        $taxItemsConverter->convert($order)->willReturn($mockedTaxItems);

        $invoiceFactory->createForData(
            '7903c83a-4c5e-4bcf-81d8-9dc304c6a353',
            '2019/03/0000001',
            '007',
            $date,
            $billingData,
            'USD',
            'en_US',
            10300,
            $mockedLineItems,
            $mockedTaxItems,
            $invoiceChannel,
            $invoiceShopBillingData
        )->willReturn($invoice);

        $this->generateForOrder($order, $date)->shouldBeLike($invoice);
    }
}
