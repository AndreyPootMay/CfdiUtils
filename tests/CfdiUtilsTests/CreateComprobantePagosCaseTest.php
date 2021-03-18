<?php

namespace CfdiUtilsTests;

use CfdiUtils\Certificado\Certificado;
use CfdiUtils\CfdiCreator33;
use CfdiUtils\Elements\Pagos10\Pagos;
use CfdiUtils\Utils\Format;

final class CreateComprobantePagosCaseTest extends TestCase
{
    public function testMoveSatDefinitionsToComprobante()
    {
        $cerfile = $this->utilAsset('certs/CSD01_AAA010101AAA.cer');
        $keyfile = $this->utilAsset('certs/CSD01_AAA010101AAA.key.pem');
        $certificado = new Certificado($cerfile);
        $fecha = strtotime('2018-03-09 10:11:12');
        $fechaPago = strtotime('2018-02-14 12:00:00');

        $creator = new CfdiCreator33();
        $comprobante = $creator->comprobante();
        $comprobante->addAttributes([
            'Fecha' => Format::datetime($fecha),
            'TipoDeComprobante' => 'P', // pago
            'LugarExpedicion' => '52000',
            'Moneda' => 'XXX',
            'Total' => '0',
            'SubTotal' => '0',
        ]);
        $creator->putCertificado($certificado, false);

        $comprobante->addEmisor([
            'Nombre' => 'ACCEM SERVICIOS EMPRESARIALES SC',
            'Rfc' => 'AAA010101AAA',
            'RegimenFiscal' => '601',
        ]);
        $comprobante->addReceptor(['Rfc' => 'COSC8001137NA', 'UsoCFDI' => 'P01']);
        // The concepto *must* have this content
        $comprobante->addConcepto([
            'ClaveProdServ' => '84111506',
            'Cantidad' => '1',
            'ClaveUnidad' => 'ACT',
            'Descripcion' => 'Pago',
            'ValorUnitario' => '0',
            'Importe' => '0',
        ]);

        // create and populate the "complemento de pagos"
        // @see \CfdiUtils\Elements\Pagos10\Pagos
        $complementoPagos = new Pagos();
        $pago = $complementoPagos->addPago([
            'FechaPago' => Format::datetime($fechaPago),
            'FormaDePagoP' => '03', // transferencia
            'MonedaP' => 'MXN',
            'Monto' => '15000.00',
            'NumOperacion' => '963852',
            'RfcEmisorCtaOrd' => 'BMI9704113PA', // Monex
            'CtaOrdenante' => '0001970000',
            'RfcEmisorCtaBen' => 'BBA830831LJ2', // BBVA
            'CtaBeneficiario' => '0198005000',
        ]);
        $pago->multiDoctoRelacionado( // add two concepts at once
            [
                'IdDocumento' => '00000000-1111-2222-3333-00000000000A',
                'MonedaDR' => 'MXN',
                'MetodoDePagoDR' => 'PPD',
                'NumParcialidad' => 2,
                'ImpSaldoAnt' => '12000.00',
                'ImpPagado' => '12000.00',
                'ImpSaldoInsoluto' => '0',
            ],
            [
                'IdDocumento' => '00000000-1111-2222-3333-00000000000B',
                'MonedaDR' => 'MXN',
                'MetodoDePagoDR' => 'PPD',
                'NumParcialidad' => 1,
                'ImpSaldoAnt' => '10000.00',
                'ImpPagado' => '3000.00',
                'ImpSaldoInsoluto' => '7000.00',
            ]
        );

        // add the "complemento de pagos" ($complementoPagos) to the $comprobante
        $comprobante->addComplemento($complementoPagos);

        // add sello and validate to assert that the specimen does not have any errors
        $creator->addSello('file://' . $keyfile, '');

        // this is after add sello to probe that it did not change the cadena origen or the sello
        $creator->moveSatDefinitionsToComprobante();

        // perform validations, it should not have any error nor warnings
        $findings = $creator->validate();
        $this->assertFalse(
            $findings->hasErrors() || $findings->hasWarnings(),
            'Created document must not contain errors, fix your test specimen'
        );

        // test that the file is the same as expected
        $expectedFile = $this->utilAsset('created-pago-with-ns-at-root.xml');
        $this->assertXmlStringEqualsXmlFile(
            $expectedFile,
            $creator->asXml(),
            'The created xml does not have root elements at root level'
        );
    }
}
