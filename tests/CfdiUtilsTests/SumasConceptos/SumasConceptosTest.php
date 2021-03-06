<?php
namespace CfdiUtilsTests\SumasConceptos;

use CfdiUtils\Elements\Cfdi33\Comprobante;
use CfdiUtils\Elements\ImpLocal10\ImpuestosLocales;
use CfdiUtils\Nodes\Node;
use CfdiUtils\SumasConceptos\SumasConceptos;
use PHPUnit\Framework\TestCase;

class SumasConceptosTest extends TestCase
{
    public function testConstructor()
    {
        $maxDiff = 0.0000001;
        $sc = new SumasConceptos(new Node('x'));
        $this->assertSame(2, $sc->getPrecision());
        $this->assertEquals(0, $sc->getSubTotal(), '', $maxDiff);
        $this->assertEquals(0, $sc->getTotal(), '', $maxDiff);
        $this->assertEquals(0, $sc->getDescuento(), '', $maxDiff);
        $this->assertEquals(0, $sc->getImpuestosRetenidos(), '', $maxDiff);
        $this->assertEquals(0, $sc->getImpuestosTrasladados(), '', $maxDiff);
        $this->assertEquals(0, $sc->getLocalesImpuestosRetenidos(), '', $maxDiff);
        $this->assertEquals(0, $sc->getLocalesImpuestosTrasladados(), '', $maxDiff);
        $this->assertCount(0, $sc->getRetenciones());
        $this->assertCount(0, $sc->getTraslados());
        $this->assertCount(0, $sc->getLocalesRetenciones());
        $this->assertCount(0, $sc->getLocalesTraslados());
        $this->assertFalse($sc->hasRetenciones());
        $this->assertFalse($sc->hasTraslados());
        $this->assertFalse($sc->hasLocalesRetenciones());
        $this->assertFalse($sc->hasLocalesTraslados());
    }

    public function providerWithConceptsDecimals()
    {
        /*
         * The case "tax uses 1 dec" 53.4 = round(35.6 + 17.8, 2)
         * The case "tax uses 6 dec" 53.33 = round(17.7776 + 35.5552, 2)
         */
        return [
            'tax uses 1 dec' => [1, 333.33, 53.4, 386.73],
            'tax uses 6 dec' => [6, 333.33, 53.33, 386.66],
        ];
    }

    /**
     * @param int $taxDecimals
     * @param float $subtotal
     * @param float $traslados
     * @param float $total
     * @dataProvider providerWithConceptsDecimals
     */
    public function testWithConceptsDecimals($taxDecimals, $subtotal, $traslados, $total)
    {
        $maxDiff = 0.0000001;
        $comprobante = new Comprobante();
        $comprobante->addConcepto([
            'Importe' => '111.11',
        ])->addTraslado([
            'Impuesto' => '002',
            'TipoFactor' => 'Tasa',
            'TasaOCuota' => '0.160000',
            'Importe' => number_format(111.11 * 0.16, $taxDecimals, '.', ''),
        ]);
        $comprobante->addConcepto([
            'Importe' => '222.22',
        ])->addTraslado([
            'Impuesto' => '002',
            'TipoFactor' => 'Tasa',
            'TasaOCuota' => '0.160000',
            'Importe' => number_format(222.22 * 0.16, $taxDecimals, '.', ''),
        ]);
        $sc = new SumasConceptos($comprobante, 2);
        $this->assertEquals($subtotal, $sc->getSubTotal(), '', $maxDiff);
        $this->assertEquals($traslados, $sc->getImpuestosTrasladados(), '', $maxDiff);
        $this->assertEquals($total, $sc->getTotal(), '', $maxDiff);
        // this are zero
        $this->assertEquals(0, $sc->getDescuento(), '', $maxDiff);
        $this->assertEquals(0, $sc->getImpuestosRetenidos(), '', $maxDiff);
        $this->assertCount(0, $sc->getRetenciones());
    }

    public function testWithImpuestosLocales()
    {
        $taxDecimals = 4;
        $maxDiff = 0.0000001;
        $comprobante = new Comprobante();
        $comprobante->addConcepto([
            'Importe' => '111.11',
        ])->addTraslado([
            'Impuesto' => '002',
            'TipoFactor' => 'Tasa',
            'TasaOCuota' => '0.160000',
            'Importe' => number_format(111.11 * 0.16, $taxDecimals, '.', ''),
        ]);
        $comprobante->addConcepto([
            'Importe' => '222.22',
        ])->addTraslado([
            'Impuesto' => '002',
            'TipoFactor' => 'Tasa',
            'TasaOCuota' => '0.160000',
            'Importe' => number_format(222.22 * 0.16, $taxDecimals, '.', ''),
        ]);
        $impuestosLocales = new ImpuestosLocales();
        $impuestosLocales->addTrasladoLocal([
            'ImpLocTrasladado' => 'IH', // fixed, taken from a sample,
            'TasadeTraslado' => '2.5',
            'Importe' => number_format(333.33 * 0.025, 2, '.', ''),
        ]);
        $comprobante->getComplemento()->add($impuestosLocales);
        $sc = new SumasConceptos($comprobante, 2);

        $this->assertCount(1, $sc->getTraslados());
        $this->assertTrue($sc->hasTraslados());
        $this->assertCount(1, $sc->getLocalesTraslados());

        $this->assertEquals(333.33, $sc->getSubTotal(), '', $maxDiff);
        $this->assertEquals(53.33, $sc->getImpuestosTrasladados(), '', $maxDiff);
        $this->assertEquals(8.33, $sc->getLocalesImpuestosTrasladados(), '', $maxDiff);
        $this->assertEquals(333.33 + 53.33 + 8.33, $sc->getTotal(), '', $maxDiff);
        // this are zero
        $this->assertEquals(0, $sc->getDescuento(), '', $maxDiff);
        $this->assertEquals(0, $sc->getImpuestosRetenidos(), '', $maxDiff);
        $this->assertCount(0, $sc->getRetenciones());
        $this->assertEquals(0, $sc->getLocalesImpuestosRetenidos(), '', $maxDiff);
        $this->assertCount(0, $sc->getLocalesRetenciones());
    }
}
