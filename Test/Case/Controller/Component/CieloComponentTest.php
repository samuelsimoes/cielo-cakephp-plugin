<?php
App::uses('Controller', 'Controller');
App::uses('Xml', 'Utility');
App::uses('HttpSocket', 'Network/Http');
App::uses('CakeResponse', 'Network');
App::uses('ComponentCollection', 'Controller');
App::uses('CieloComponent', 'Cielo.Controller/Component');

class CieloComponentTest extends CakeTestCase {

	public function setUp() {
		parent::setUp();
		Configure::write('Cielo', array(
			'testando' => true,
			'buy_page_cielo' => false
		));
		$Collection = new ComponentCollection();
		$this->CieloComponent = new CieloComponent($Collection);
	}

	public function testConverterValorFormatoCielo() {
		$valor1 = $this->CieloComponent->converterValor(29.25);
		$valor2 = $this->CieloComponent->converterValor(1900.01);
		$valor3 = $this->CieloComponent->converterValor(1.05);
		
		$this->assertEquals(2925, $valor1);
		$this->assertEquals(190001, $valor2);
		$this->assertEquals(105, $valor3);
	}

	public function testConverterDataFormatoCakeParaCielo() {

		$data1 = $this->CieloComponent->converterData('2012-09-10 18:02:01');
		$data2 = $this->CieloComponent->converterData('1990-11-22 01:00:01');
		
		$this->assertEquals('2012-09-10T18:02:01', $data1);
		$this->assertEquals('1990-11-22T01:00:01', $data2);
	}

	public function testConverterDataFormatoCieloParaCake() {

		$data1 = $this->CieloComponent->converterDataCieloParaDateTime('2012-05-07T08:53:54.978-03:00');
		$data2 = $this->CieloComponent->converterDataCieloParaDateTime('2012-12-25T19:50:00.000-03:00');
		
		$this->assertEquals('2012-05-07 08:53:54', $data1);
		$this->assertEquals('2012-12-25 19:50:00', $data2);

	}

	public function testMontarXmlRequisicaoTransacao() {

		$this->CieloComponent->autorizar = 3;

		$this->CieloComponent->xml_id = "6560a94c-663b-4aec-9a45-e45f278e00b4";

		$this->CieloComponent->cc_numero = 4012001038443335;
		$this->CieloComponent->cc_validade = 201501;
		$this->CieloComponent->cc_codigo_seguranca = 585;
		$this->CieloComponent->cc_bandeira = 'visa';
		$this->CieloComponent->cc_produto = 2;

		$this->CieloComponent->pagamento_qtd_parcelas = 2;

		$this->CieloComponent->pedido_id = 1503604566;
		$this->CieloComponent->pedido_valor = 100;
		$this->CieloComponent->pedido_data_hora = "2010-07-14T15:50:11";

		$xml = $this->CieloComponent->montarXml('requisicao-transacao');

		$this->assertEquals('<?xml version="1.0" encoding="ISO-8859-1"?>
<requisicao-transacao xmlns="http://ecommerce.cbmp.com.br" versao="1.2.0" id="6560a94c-663b-4aec-9a45-e45f278e00b4"><dados-ec><numero>1006993069</numero><chave>25fbb99741c739dd84d7b06ec78c9bac718838630f30b112d033ce2e621b34f3</chave></dados-ec><dados-portador><numero>4012001038443335</numero><validade>201501</validade><indicador>1</indicador><codigo-seguranca>585</codigo-seguranca></dados-portador><dados-pedido><numero>1503604566</numero><valor>100</valor><moeda>986</moeda><data-hora>2010-07-14T15:50:11</data-hora><idioma>PT</idioma></dados-pedido><forma-pagamento><bandeira>visa</bandeira><produto>2</produto><parcelas>2</parcelas></forma-pagamento><url-retorno>null</url-retorno><autorizar>3</autorizar><capturar>false</capturar></requisicao-transacao>
', $xml);
	}

	public function testMontarXmlConsultarTransacao() {

		$this->CieloComponent->xml_id = "6560a94c-663b-4aec-9a45-e45f278e00b4";

		$this->CieloComponent->tid = "100699306914AC581001";
		$this->CieloComponent->loja_id = "1001734898";
		$this->CieloComponent->loja_chave = "e84827130b9837473681c2787007da5914d6359947015a5cdb2b8843db0fa832";

		$xml = $this->CieloComponent->montarXml('requisicao-consulta');

		$this->assertEquals('<?xml version="1.0" encoding="ISO-8859-1"?>
<requisicao-consulta xmlns="http://ecommerce.cbmp.com.br" versao="1.2.0" id="6560a94c-663b-4aec-9a45-e45f278e00b4"><tid>100699306914AC581001</tid><dados-ec><numero>1001734898</numero><chave>e84827130b9837473681c2787007da5914d6359947015a5cdb2b8843db0fa832</chave></dados-ec></requisicao-consulta>
', $xml);

	}

	public function testMontarXmlCancelarTransacao() {

		$this->CieloComponent->xml_id = "6560a94c-663b-4aec-9a45-e45f278e00b4";

		$this->CieloComponent->tid = "100699306914AC581001";
		$this->CieloComponent->loja_id = "1001734898";
		$this->CieloComponent->loja_chave = "e84827130b9837473681c2787007da5914d6359947015a5cdb2b8843db0fa832";

		$xml = $this->CieloComponent->montarXml('requisicao-cancelamento');

		$this->assertEquals('<?xml version="1.0" encoding="ISO-8859-1"?>
<requisicao-cancelamento xmlns="http://ecommerce.cbmp.com.br" versao="1.2.0" id="6560a94c-663b-4aec-9a45-e45f278e00b4"><tid>100699306914AC581001</tid><dados-ec><numero>1001734898</numero><chave>e84827130b9837473681c2787007da5914d6359947015a5cdb2b8843db0fa832</chave></dados-ec></requisicao-cancelamento>
', $xml);

	}

	public function testMontarXmlCapturarTransacao() {

		$this->CieloComponent->xml_id = "6560a94c-663b-4aec-9a45-e45f278e00b4";

		$this->CieloComponent->tid = "100699306914AC581001";
		$this->CieloComponent->loja_id = "1001734898";
		$this->CieloComponent->loja_chave = "e84827130b9837473681c2787007da5914d6359947015a5cdb2b8843db0fa832";
		$this->CieloComponent->valor_capturar = 2000;

		$xml = $this->CieloComponent->montarXml('requisicao-captura');

		$this->assertEquals('<?xml version="1.0" encoding="ISO-8859-1"?>
<requisicao-captura xmlns="http://ecommerce.cbmp.com.br" versao="1.2.0" id="6560a94c-663b-4aec-9a45-e45f278e00b4"><tid>100699306914AC581001</tid><dados-ec><numero>1001734898</numero><chave>e84827130b9837473681c2787007da5914d6359947015a5cdb2b8843db0fa832</chave></dados-ec><valor>2000</valor></requisicao-captura>
', $xml);

	}

	public function criarTransacao() {

		$this->CieloComponent->testando = true;
		$this->CieloComponent->buy_page_cielo = false;
		$this->CieloComponent->autorizar = 3;

		$this->CieloComponent->xml_id = uniqid();

		$this->CieloComponent->cc_numero = '4012001038443335';
		$this->CieloComponent->cc_validade = '201501';
		$this->CieloComponent->cc_codigo_seguranca = '585';
		$this->CieloComponent->cc_bandeira = 'visa';
		$this->CieloComponent->cc_produto = 2;


		$this->CieloComponent->pedido_id = 45;
		$this->CieloComponent->pedido_valor = 2500;
		$this->CieloComponent->pedido_data_hora = '2010-07-14T15:50:11';

		$this->CieloComponent->pagamento_qtd_parcelas = 2;

		return $this->CieloComponent->finalizar();
	}

	public function testCriarTransacaOk() {

		$transacao = $this->criarTransacao();

		$expectativa = array(
			'transacao' => array(
				'@versao' => '1.2.0',
				'@id' => $this->CieloComponent->xml_id,
				'tid' => $transacao['transacao']['tid'],
				'pan' => $transacao['transacao']['pan'],
				'dados-pedido' => array(
					'numero' => 45,
					'valor' => '2500',
					'moeda' => '986',
					'data-hora' => $transacao['transacao']['dados-pedido']['data-hora'],
					'idioma' => 'PT'
				),
				'forma-pagamento' => array(
					'bandeira' => 'visa',
					'produto' => '2',
					'parcelas' => '2'
				),
				'status' => '4',
				'autenticacao' => array(
					'codigo' => '4',
					'mensagem' => 'Transacao sem autenticacao',
					'data-hora' => $transacao['transacao']['autenticacao']['data-hora'],
					'valor' => '2500',
					'eci' => '7'
				),
				'autorizacao' => array(
					'codigo' => '4',
					'mensagem' => 'Transação autorizada',
					'data-hora' => $transacao['transacao']['autorizacao']['data-hora'],
					'valor' => '2500',
					'lr' => '00',
					'arp' => $transacao['transacao']['autorizacao']['arp'],
					'nsu' => $transacao['transacao']['autorizacao']['nsu']
				)
			)
		);

		$this->assertEquals($expectativa, $transacao);
	}

	public function testConsultarTransacaoOk() {

		$this->CieloComponent->xml_id = uniqid();

		$transacao = $this->criarTransacao();

		$tid = $transacao['transacao']['tid'];

		$retorno = $this->CieloComponent->consultarTransacao($tid);

		$expectativa = array(
			'transacao' => array(
				'@versao' => '1.2.0',
				'@id' => $this->CieloComponent->xml_id,
				'tid' => $retorno['transacao']['tid'],
				'pan' => $retorno['transacao']['pan'],
				'dados-pedido' => array(
					'numero' => '45',
					'valor' => '2500',
					'moeda' => '986',
					'data-hora' => $retorno['transacao']['dados-pedido']['data-hora'],
					'idioma' => 'PT'
				),
				'forma-pagamento' => array(
					'bandeira' => 'visa',
					'produto' => '2',
					'parcelas' => '2'
				),
				'status' => '4',
				'autenticacao' => array(
					'codigo' => '4',
					'mensagem' => 'Transacao sem autenticacao',
					'data-hora' => $retorno['transacao']['autenticacao']['data-hora'],
					'valor' => '2500',
					'eci' => '7'
				),
				'autorizacao' => array(
					'codigo' => '4',
					'mensagem' => 'Transação autorizada',
					'data-hora' => $retorno['transacao']['autorizacao']['data-hora'],
					'valor' => '2500',
					'lr' => '00',
					'arp' => $retorno['transacao']['autorizacao']['arp'],
					'nsu' => $retorno['transacao']['autorizacao']['nsu']
				)
			)
		);

		$this->assertEquals($expectativa, $retorno);

	}

	public function testCancelarTransacaoOk() {

		$this->CieloComponent->xml_id = uniqid();

		$transacao = $this->criarTransacao();

		$tid = $transacao['transacao']['tid'];

		$retorno = $this->CieloComponent->cancelarTransacao($tid);

		$expectativa = array(
			'transacao' => array(
				'@versao' => '1.2.0',
				'@id' => $this->CieloComponent->xml_id,
				'tid' => $retorno['transacao']['tid'],
				'pan' => $retorno['transacao']['pan'],
				'dados-pedido' => array(
					'numero' => '45',
					'valor' => '2500',
					'moeda' => '986',
					'data-hora' => $retorno['transacao']['dados-pedido']['data-hora'],
					'idioma' => 'PT'
				),
				'forma-pagamento' => array(
					'bandeira' => 'visa',
					'produto' => '2',
					'parcelas' => '2'
				),
				'status' => '9',
				'autenticacao' => array(
					'codigo' => '9',
					'mensagem' => 'Transacao sem autenticacao',
					'data-hora' => $retorno['transacao']['autenticacao']['data-hora'],
					'valor' => '2500',
					'eci' => '7'
				),
				'autorizacao' => array(
					'codigo' => '9',
					'mensagem' => 'Transação autorizada',
					'data-hora' => $retorno['transacao']['autorizacao']['data-hora'],
					'valor' => '2500',
					'lr' => '00',
					'arp' => $retorno['transacao']['autorizacao']['arp'],
					'nsu' => $retorno['transacao']['autorizacao']['nsu']
				),
				'cancelamentos' => array(
					'cancelamento' => array(
						'codigo' => '9',
						'mensagem' => 'Transação desfeita',
						'data-hora' => $retorno['transacao']['cancelamentos']['cancelamento']['data-hora'],
						'valor' => '2500'
					)
				)
			)
		);

		$this->assertEquals($expectativa, $retorno);

	}

	public function testCapturarTransacaoOk() {

		$transacao = $this->criarTransacao();

		$tid = $transacao['transacao']['tid'];

		$retorno = $this->CieloComponent->capturarTransacao($tid);

		$expectativa = array(
			'transacao' => array(
				'@versao' => '1.2.0',
				'@id' => $this->CieloComponent->xml_id,
				'tid' => $transacao['transacao']['tid'],
				'pan' => $retorno['transacao']['pan'],
				'dados-pedido' => array(
					'numero' => '45',
					'valor' => '2500',
					'moeda' => '986',
					'data-hora' => $retorno['transacao']['dados-pedido']['data-hora'],
					'idioma' => 'PT'
				),
				'forma-pagamento' => array(
					'bandeira' => 'visa',
					'produto' => '2',
					'parcelas' => '2'
				),
				'status' => '6',
				'autenticacao' => array(
					'codigo' => '6',
					'mensagem' => 'Transacao sem autenticacao',
					'data-hora' => $retorno['transacao']['autenticacao']['data-hora'],
					'valor' => '2500',
					'eci' => '7'
				),
				'autorizacao' => array (
					'codigo' => '6',
					'mensagem' => 'Transação autorizada',
					'data-hora' => $retorno['transacao']['autorizacao']['data-hora'],
					'valor' => '2500',
					'lr' => '00',
					'arp' => $retorno['transacao']['autorizacao']['arp'],
					'nsu' => $retorno['transacao']['autorizacao']['nsu']
				),

				'captura' => array (
					'codigo' => '6',
					'mensagem' => 'Transacao capturada com sucesso',
					'data-hora' => $retorno['transacao']['captura']['data-hora'],
					'valor' => '2500'
				)
			)
		);

		$this->assertEquals($expectativa, $retorno);

	}

	public function testCapturarTransacaoValorParcialOk() {

		$transacao = $this->criarTransacao();

		$tid = $transacao['transacao']['tid'];

		$retorno = $this->CieloComponent->capturarTransacao($tid, 1000);

		$expectativa = array(
			'transacao' => array(
				'@versao' => '1.2.0',
				'@id' => $this->CieloComponent->xml_id,
				'tid' => $transacao['transacao']['tid'],
				'pan' => $retorno['transacao']['pan'],
				
				'dados-pedido' => array(
					'numero' => '45',
					'valor' => '2500',
					'moeda' => '986',
					'data-hora' => $retorno['transacao']['dados-pedido']['data-hora'],
					'idioma' => 'PT'
				),
				
				'forma-pagamento' => array(
					'bandeira' => 'visa',
					'produto' => '2',
					'parcelas' => '2'
				),

				'status' => '6',
				
				'autenticacao' => array(
					'codigo' => '6',
					'mensagem' => 'Transacao sem autenticacao',
					'data-hora' => $retorno['transacao']['autenticacao']['data-hora'],
					'valor' => '2500',
					'eci' => '7'
				),

				'autorizacao' => array (
					'codigo' => '6',
					'mensagem' => 'Transação autorizada',
					'data-hora' => $retorno['transacao']['autorizacao']['data-hora'],
					'valor' => '2500',
					'lr' => '00',
					'arp' => $retorno['transacao']['autorizacao']['arp'],
					'nsu' => $retorno['transacao']['autorizacao']['nsu']
				),

				'captura' => array (
					'codigo' => '6',
					'mensagem' => 'Transacao capturada com sucesso',
					'data-hora' => $retorno['transacao']['captura']['data-hora'],
					'valor' => '1000'
				)
			)
		);

		$this->assertEquals($expectativa, $retorno);

	}
}