<?php

namespace Royopa\SicinBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;
use Ghunti\HighchartsPhpBundle\HighchartsPHP\Highchart;
use Ghunti\HighchartsPhpBundle\HighchartsPHP\HighchartJsExpr;

/**
 * Cotação controller.
 *
 * @Route("/cotacao")
 */
class CotacaoController extends Controller
{

    /**
     * Default Page.
     *
     * @Route("/", name="cotacao")
     * @Method("GET")
     * @Template("RoyopaSicinBundle:Cotacao:index.html.twig")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('RoyopaSicinBundle:Ativo')
            ->findBy(
                array('tipo' => 7)
            );

        return array(
            'entities' => $entities,
        );
    }

    /**
     * Default Page.
     *
     * @Route("/{codigo}/show", name="cotacao_show")
     * @Method("GET")
     * @Template("RoyopaSicinBundle:Cotacao:show.html.twig")
     */
    public function showAction($codigo)
    {
        $client = new \Scheb\YahooFinanceApi\ApiClient();

        $ativo = $client->search($codigo);

        if (count($ativo['ResultSet']['Result']) == 0) {
            return new Response('Ativo não encontrado.');
        }

        $ativo = $ativo['ResultSet']['Result'][0];

        $dataInicial = new \DateTime('now');
        $interval = new \DateInterval('P3M');
        $interval->invert = 1;
        $dataInicial = $dataInicial->add($interval);

        $chart = new Highchart(Highchart::HIGHSTOCK);
        $chart->chart->renderTo = "container";
        $chart->rangeSelector->selected = 1;
        $chart->title->text = "AAPL Stock Price";
        $chart->series[] = array(
            'name' => "AAPL",
            'data' => new HighchartJsExpr("data"),
            'tooltip' => array(
                'valueDecimals' => 2,
            ),
        );

        //Get historical data
        $data = $client->getHistoricalData(
            $ativo['symbol'],
            $dataInicial,
            new \DateTime('now')
        );

        return array(
            'ativo'    => $ativo,
            'entities' => $data['query']['results'],
            'chart'    => $chart,
        );
    }
}
