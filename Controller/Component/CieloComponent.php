<?php
App::uses('Component', 'Controller');
App::uses('HttpSocket', 'Network/Http');
App::uses('CakeTime', 'Utility');

/**
 *
 * Cielo Component, desenvolvido para auxiliar nas tarefas envolvendo 
 * o webservice de transações da Cielo para eCommerces, bem como redirecionamentos 
 * e tratamento dos retornos.
 * 
 *
 * PHP versions 5+
 * Copyright 2010-2012, Samuel Simões (@samuelsimoes)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @author      Samuel Simões
 * @link        https://github.com/samuelsimoes/cielo-cakephp-plugin/
 * @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @version     2.1
 */

class CieloComponent extends Component {

	/**
	 *
	 * Instancia do Controller
	 * @var Controller
	 */
	public $Controller = null;

	public $components = array('Session');

	/**
	 * Se vai usar o ambiente de teste da Cielo.
	 * @var bool
	 */
	public $testando = true;

	/**
	 * ID da Loja junto a Cielo
	 * @var string
	 */
	public $loja_id = null;

	/**
	 * Chave da Loja junto a Cielo
	 * @var string
	 */
	public $loja_chave = null;
	
	/**
	 * Número de identificação do pedido no ecommerce
	 * @var mixed
	 */
	public $pedido_id = null;

	/**
	 * Valor do pedido no formato 100 (o que seria R$1,00)
	 * @var int
	 */
	public $pedido_valor = null;

	/**
	 * Data do pedido no formato 2012-09-10T18:02:01
	 * @var string
	 */
	public $pedido_data_hora = null;

	/**
	 * Bandeira do Cartão de Crédito envolvido na Transação
	 * 
	 * Opções:
	 * 
	 * 'visa',
	 * 
	 * 'mastercard',
	 * 
	 * 'diners',
	 * 
	 * 'discover',
	 * 
	 * 'elo',
	 * 
	 * 'amex'
	 * 
	 * @var string
	 */
	public $cc_bandeira = null;

	/**
	 * Produto da Cielo escolhido par a transação
	 * 
	 * Possibilidades:
	 * 
	 * 1: Crédito à Vista,
	 * 
	 * 2: Parcelado na loja,
	 * 
	 * 3: Parcelado na Administradora,
	 * 
	 * 'A': Débito
	 * 
	 * @var mixed
	 */
	public $cc_produto = null;

	/**
	 * Número do Cartão de Crédito
	 * @var int
	 */
	public $cc_numero = null;

	/**
	 * Validade do Cartão no formato 201202 = 02/2012
	 * @var int
	 */
	public $cc_validade = null;

	/**
	 * Código de Segurança do Cartão
	 * @var int
	 */
	public $cc_codigo_seguranca = null;

	/**
	 * Quantidade de Parcelas envolvidas na transação.
	 * @var int
	 */
	public $pagamento_qtd_parcelas = null;
	
	/**
	 * URL do Webservice da Cielo. Isso é definido automaticamente,
	 * basta configurar a opção Cielo.testando como TRUE ou FAlSE.
	 * @var string
	 */
	public $ws_url = null;

	/**
	 * URL utilizada no retorno caso você tenha escolhido BuyPage Cielo ou
	 * autenticações.
	 * @var string
	 */
	public $url_retorno = 'null';

	/**
	 * Indicador de como vai proceder a autorização do pedido junto a Cielo.
	 * 
	 * Possibilidades: 
	 * 
	 * 0: Não autorizar,
	 * 
	 * 1: Autorizar somente se autenticada, 
	 * 
	 * 2: Autorizar autenticada e não autenticada,
	 * 
	 * 3: Autorizar sem passar por autenticação (somente para crédito) – também
	 * conhecida como “Autorização Direta”. Obs.: Para Diners, Discover, Elo e Amex 
	 * o valor será sempre “3”, pois estas bandeiras não possuem programa de 
	 * autenticação.
	 * 
	 * 4: Transação Recorrente.
	 * 
	 * @var int
	 */
	public $autorizar = 1;

	/**
	 * Define se a transação será automaticamente capturada caso seja autorizada.
	 * @var bool
	 */
	public $capturar = false;

	/**
	 * Valor a ser capturado
	 * @var int
	 */
	public $valor_capturar = false;

	/**
	 * ID do XML enviado na requisição a Cielo.
	 * @var string
	 */
	public $xml_id = null;

	/**
	 * Indicador se a compra vai acontecer na loja ou na Buy Page Cielo.
	 * @var boolean
	 */
	public $buy_page_cielo = false;

	/**
	 * Indicador de redirecionamento para autorização ou Buy Page Cielo
	 * @var boolean
	 */
	public $redirecionar = false;

	/**
	 * Array público que guarda o último erro que aconteceu na requisição.
	 * @var array
	 */
	public $erro = null;

	/**
	 * Caminho para o certificado SSL
	 * @var string
	 */
	public $certificado_ssl = null;

	public function __construct(ComponentCollection $collection, $settings = array()) {
		parent::__construct($collection, $settings);

		$this->testando = Configure::read('Cielo.testando');
		$this->buy_page_cielo = Configure::read('Cielo.buy_page_cielo');

		if(!Configure::read('Cielo.caminho_certificado_ssl'))
		{
			$this->certificado_ssl = APP . '/Plugin/Cielo/VeriSignClass3PublicPrimaryCertificationAuthority-G5.crt';
		} else {
			$this->certificado_ssl = Configure::read('Cielo.caminho_certificado_ssl');
		}

		/**
		 * Define a URL de retorno e os dados da Loja de acordo com a flag "testando"
		 */
		if($this->testando)
		{
			$this->ws_url = "https://qasecommerce.cielo.com.br/servicos/ecommwsec.do";

			if($this->buy_page_cielo)
			{
				$this->loja_id = '1001734898';
				$this->loja_chave = 'e84827130b9837473681c2787007da5914d6359947015a5cdb2b8843db0fa832';
			}
			else
			{
				$this->loja_id = '1006993069';
				$this->loja_chave = '25fbb99741c739dd84d7b06ec78c9bac718838630f30b112d033ce2e621b34f3';
			}
		}
		else
		{
			$this->ws_url = "https://ecommerce.cbmp.com.br/servicos/ecommwsec.do";
			
			$this->loja_id = Configure::read('Cielo.loja_id');
			$this->loja_chave = Configure::read('Cielo.loja_chave');
		}

		$this->xml_id = uniqid();
	}

	public function startup(Controller $controller)
	{
		parent::startup($controller);
		$this->Controller = $controller;
	}

	/**
	 * Método responsável por fazer uma pré análise dos atributos da transação,
	 * baseado em algumas regras de negócio da Cielo.
	 */
	public function analisarParametros()
	{

		if(is_null($this->loja_id))
			throw new Exception("Não foi definido o ID da loja junto a Cielo.");

		if(is_null($this->loja_chave))
			throw new Exception("Não foi definido a chave da loja junto a Cielo.");

		if(is_null($this->pedido_id))
			throw new Exception("Não foi definido um ID interno do pedido para a transação.");

		if(is_null($this->pedido_valor))
			throw new Exception("Não foi definido o valor da transação.");

		if(is_null($this->cc_produto))
			throw new Exception("Não foi definido o Cielo Produto para a transação.");

		if(!in_array($this->cc_produto, array(1, 2, 3, 'A')))
			throw new Exception("O Cielo Produto escolhido não é válido.");

		if(!in_array($this->cc_bandeira, array('visa', 'mastercard', 'diners', 'elo', 'amex')))
			throw new Exception("A bandeira definida não é válida.");

		if($this->cc_produto == 'A' and $this->pagamento_qtd_parcelas != 1)
			throw new Exception("Apenas uma parcela é permitida para cartões de débito.");

		if($this->cc_produto == 1 and $this->pagamento_qtd_parcelas != 1)
			throw new Exception("Apenas uma parcela é permitida para crédito à vista.");

		if($this->pagamento_qtd_parcelas > 1) {
			if(($this->pedido_valor/$this->pagamento_qtd_parcelas) <= 500)
				throw new Exception("O valor das parcelas não pode ser menor que 5 reais.");
		}

		if(in_array($this->autorizar, array(1, 2)) and (is_null($this->url_retorno) or ($this->url_retorno == "null")) )
			throw new Exception("Para o método de autorização escolhido você deve definir uma URL de retorno.");
	}

	/**
	 * Método responsável por converter a data do formato Cake para o da Cielo
	 * para ser usada na construção dos XML's
	 * 
	 * @param  string $data Exemplo formato: 1990-05-05 15:00:00
	 * @return string       Data no formato: 1990-05-05T150:00:00
	 */
	public function converterData($data=null)
	{
		if(is_null($data)) return false;
		return CakeTime::format('Y-m-d', $data) . 'T' . CakeTime::format('H:i:s', $data);
	}


	/**
	 * Método responsável por converter a data do formato Cielo para Cake.
	 * Utilizando na conversão das respostas do WebService.
	 * 
	 * @param  string $data Data no formato: 2012-05-07T08:53:54.978-03:00
	 * @return strong       Data no formato: 2012-05-07 08:53:54
	 */
	public function converterDataCieloParaDateTime($data=null)
	{
		if(is_null($data)) return false;

		$ano = explode('T', $data);
		$hora = $ano[1];
		$hora = explode('.', $hora);

		return $ano[0] . ' ' . $hora[0];
	}

	/**
	 * Método responsável por converter valores em float para INT no formato
	 * suportado pelo WebService da Cielo.
	 * 
	 * @param  float $valor ex: 20.20
	 * @return int          ex: 2020
	 */
	public function converterValor($valor=null)
	{
		if(is_null($valor)) return false;
		return number_format($valor, 2, '','');
	}

	public function tratarDatasRetorno($resultado=null)
	{
		if(is_null($resultado)) return false;

		if(isset($resultado['transacao']['dados-pedido']['data-hora']))
			$resultado['transacao']['dados-pedido']['data-hora'] = $this->converterDataCieloParaDateTime($resultado['transacao']['dados-pedido']['data-hora']);

		if(isset($resultado['transacao']['autenticacao']['data-hora']))
			$resultado['transacao']['autenticacao']['data-hora'] = $this->converterDataCieloParaDateTime($resultado['transacao']['autenticacao']['data-hora']);

		if(isset($resultado['transacao']['autorizacao']['data-hora']))
			$resultado['transacao']['autorizacao']['data-hora'] = $this->converterDataCieloParaDateTime($resultado['transacao']['autorizacao']['data-hora']);

		if(isset($resultado['transacao']['cancelamentos']['cancelamento']['data-hora']))
			$resultado['transacao']['cancelamentos']['cancelamento']['data-hora'] = $this->converterDataCieloParaDateTime($resultado['transacao']['cancelamentos']['cancelamento']['data-hora']);

		if(isset($resultado['transacao']['captura']['data-hora']))
			$resultado['transacao']['captura']['data-hora'] = $this->converterDataCieloParaDateTime($resultado['transacao']['captura']['data-hora']);

		return $resultado;
	}

	/**
	 * Método responsável por enviar os XML's para o WebService da Cielo.
	 * 
	 * @param  string $xml XML contendo as informações das requisições.
	 * @return mixed       Se tudo certo o retorno será o XML de retorno
	 * da cielo já transformado em array, em caso de erro é lançado uma
	 * exceção com o erro e código retornado pelo WebService da Cielo.
	 */
	public function enviar($xml)
	{
		$sessao_curl = curl_init();
		curl_setopt($sessao_curl, CURLOPT_URL, $this->ws_url);
		curl_setopt($sessao_curl, CURLOPT_FAILONERROR, true);
		curl_setopt($sessao_curl, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($sessao_curl, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($sessao_curl, CURLOPT_CAINFO, $this->certificado_ssl);
		curl_setopt($sessao_curl, CURLOPT_SSLVERSION, 3);
		curl_setopt($sessao_curl, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($sessao_curl, CURLOPT_TIMEOUT, 40);
		curl_setopt($sessao_curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($sessao_curl, CURLOPT_POST, true);
		curl_setopt($sessao_curl, CURLOPT_POSTFIELDS, "mensagem=". $xml);

		$resultado = curl_exec($sessao_curl);
		curl_close($sessao_curl);

		$resultado = Xml::toArray(Xml::build($resultado));

		if(isset($resultado['erro'])) {
			throw new Exception($resultado['erro']['mensagem'], $resultado['erro']['codigo']);
		} else {
			$resultado = $this->tratarDatasRetorno($resultado);
			return $resultado;
		}
	}

	/**
	 * Método responsável por trazer as informações das transações, tanto em redirects
	 * como diretas.
	 */
	public function retornoTransacao()
	{

		$tid = $this->Session->read('CieloTidRedirecionamento');
		$this->Session->delete('CieloTidRedirecionamento');
		
		return ($tid) ? $this->consultarTransacao($tid) : false;
	}

	/**
	 * Método responsável por finalizar uma transação depois que os dados tiverem 
	 * sido corretamente definidos.
	 * 
	 * Caso o método de autorização esteja definido como 0, 1 ou 2, métodos que precisam
	 * ser autorizados pelos bancos/bandeiras, haverá um redirect e logo em seguida o usuário
	 * voltará para a página do pagamento, porém os dados estarão disponíveis no método
	 * $this->transacao_retorno(), outra coisa importante a se observar é que caso você tenha
	 * escolhido um desses métodos você deve definir a URL de retorno em $this->url_retorno.
	 * 
	 * Caso o método escolhido seja autorização direta ou algo parecido você pode capturar os
	 * dados da transação já no $this->finalizar(), no retorno, pois não haverá redirect.
	 */
	public function finalizar()
	{
		/**
		 * Caso as autorização definida juntamente com a bandeira necessite de redirect mesmo
		 * em caso de BuyPage Loja ou o BuyPage Cielo esteja definido como padrão o atributo
		 * 'redirecionar' é definido como true.
		 */
		if( (in_array($this->autorizar, array(0, 1, 2)) and in_array($this->cc_bandeira, array('visa', 'mastercard'))) or  $this->buy_page_cielo )
			$this->redirecionar = true;

		/**
		 * Caso seja definido apenas uma parcela o produto automaticamente
		 * é definido como crédito a vista.
		 */
		if($this->pagamento_qtd_parcelas == 1) $this->cc_produto = 1;

		if($this->pagamento_qtd_parcelas == 'A')
		{
			$this->cc_produto = 'A';
			$this->pagamento_qtd_parcelas = 1;
		}

		try {
			$this->analisarParametros();
		} catch (Exception $e) {
			$this->erro = array();
			$this->erro['mensagem'] = $e->getMessage();
			return false;
		}

		try {
			$retorno = $this->enviar($this->montarXml('requisicao-transacao'));
			$this->Session->write('CieloTidRedirecionamento', $retorno['transacao']['tid']);
		} catch (Exception $e) {
			$this->erro = array();
			$this->erro['mensagem'] = $e->getMessage();
			$this->erro['codigo'] = $e->getCode();
			return false;
		}

		if($this->redirecionar) $this->Controller->redirect($retorno['transacao']['url-autenticacao']);
		return $retorno;
	}

	/**
	 * Método responsável por verificar o Cielo TID setado.
	 * 
	 * @param  string $tid Cielo TID.
	 * @return mixed       Caso ocorra com sucesso é retornado o Cielo TID,
	 * caso falso retorna false.
	 */
	protected function verificarCieloTid($tid=null)
	{
		if(is_null($tid)) {
			$this->erro['mensagem'] = "Entre com um Cielo TID";
			return false;
		}

		$this->tid = $tid;

		return $this->tid;
	}

	public function consultarTransacao($tid=null)
	{
		if (!$this->verificarCieloTid($tid)) return false;

		try {
			return $this->enviar($this->montarXml('requisicao-consulta'));
		} catch (Exception $e) {
			$this->erro = array();
			$this->erro['mensagem'] = $e->getMessage();
			$this->erro['codigo'] = $e->getCode();
			return false;
		}
	}

	public function capturarTransacao($tid=null, $valor=null)
	{
		if (!$this->verificarCieloTid($tid)) return false;

		$this->valor_capturar = $valor;

		try {
			return $this->enviar($this->montarXml('requisicao-captura'));
		} catch (Exception $e) {
			$this->erro = array();
			$this->erro['mensagem'] = $e->getMessage();
			$this->erro['codigo'] = $e->getCode();
			return false;
		}
	}

	public function cancelarTransacao($tid=null)
	{
		if (!$this->verificarCieloTid($tid)) return false;

		try {
			return $this->enviar($this->montarXml('requisicao-cancelamento'));
		} catch (Exception $e) {
			$this->erro = array();
			$this->erro['mensagem'] = $e->getMessage();
			$this->erro['codigo'] = $e->getCode();
			return false;
		}
	}

	public function montarXml($tipo=null)
	{
		$elemento_base = $tipo;

		$xml[$elemento_base] = array(
			'@versao' => '1.2.0',
			'@id' => $this->xml_id,
			'@xmlns' => 'http://ecommerce.cbmp.com.br'
		);

		if($tipo == "requisicao-cancelamento" or $tipo == "requisicao-consulta" or $tipo == "requisicao-captura")
		{
			$xml[$elemento_base]['tid'] = $this->tid;
		}

		$xml[$elemento_base]['dados-ec']['numero'] = $this->loja_id;
		$xml[$elemento_base]['dados-ec']['chave'] = $this->loja_chave;

		if($tipo == "requisicao-transacao")
		{
			if(!$this->buy_page_cielo)
			{
				$xml[$elemento_base]['dados-portador']['numero'] = $this->cc_numero;
				$xml[$elemento_base]['dados-portador']['validade'] = $this->cc_validade;
				$xml[$elemento_base]['dados-portador']['indicador'] = '1';
				$xml[$elemento_base]['dados-portador']['codigo-seguranca'] = $this->cc_codigo_seguranca;
			}

			$xml[$elemento_base]['dados-pedido']['numero'] = $this->pedido_id;
			$xml[$elemento_base]['dados-pedido']['valor'] = $this->pedido_valor;
			$xml[$elemento_base]['dados-pedido']['moeda'] = '986';
			$xml[$elemento_base]['dados-pedido']['data-hora'] = $this->pedido_data_hora;
			$xml[$elemento_base]['dados-pedido']['idioma'] = 'PT';

			$xml[$elemento_base]['forma-pagamento']['bandeira'] = $this->cc_bandeira;
			$xml[$elemento_base]['forma-pagamento']['produto'] = $this->cc_produto;
			$xml[$elemento_base]['forma-pagamento']['parcelas'] = $this->pagamento_qtd_parcelas;

			$xml[$elemento_base]['url-retorno'] = $this->url_retorno;

			$xml[$elemento_base]['autorizar'] = $this->autorizar;

			$xml[$elemento_base]['capturar'] = $this->capturar ? 'true' : 'false';
		}

		if(!is_null($this->valor_capturar) and $tipo == "requisicao-captura") {
			$xml[$elemento_base]['valor'] = $this->valor_capturar;
		}

		$xml = Xml::fromArray($xml, array('encoding' => 'ISO-8859-1', 'format' => 'tags'));
		return $xml->asXML();
	}
}