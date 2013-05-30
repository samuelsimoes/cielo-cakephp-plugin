#CakePHP Cielo  Plugin
-----------------------------------------

O Cielo CakePHP Plugin foi desenvolvido para auxiliar nas tarefas envolvendo o webservice de transações da Cielo para eCommerces, bem como redirecionamentos e tratamento dos retornos.

Apesar dos meus esforços para documentar o plugin eu recomendo fortemente a leitura do manual oficial para desenvolvedores da Cielo.

##Requisitos

*	CakePHP 2.x
*	PHP 5.2.x ou Superior
*	Extensão php_curl

##Instalação

*	Clone/Copie os arquivos no diretório `app/Plugin/Cielo`
*	No `app/Config/bootstrap.php` carregue o plugin com `CakePlugin::load('Cielo');`
*	Inclua o component de transações no seu `AppController.php`, `public $components = array('Cielo.Cielo');`

##Configuração

Em sua aplicação, preferencialmente em `app/Config/bootstrap.php`, defina as configurações que vão ser usadas pelo plugin usando a classe nativa do Cake 'Configure', abaixo um exemplo:

```php
<?php
Configure::write('Cielo',array(
    'testando' => true, #Se vai utilizar o ambiente de testes da Cielo
    'buy_page_cielo' => true
    'loja_id' => 1001734898,
    'loja_chave' => 'e84827130b9837473681c2787007da5914d6359947015a5cdb2b8843db0fa832',
    'caminho_certificado_ssl' => APP . '/VeriSignClass3PublicPrimaryCertificationAuthority-G5.crt'
));
```
    
##Uso

O uso do plugin é bastante simples e todo o processo se dá com o uso do component Cielo no seu controller.

Você precisa definir as informações da transação e usar o método **$this->Cielo->finalizar()**.

O retorno será **false** caso a transação não seja concluída com sucesso, ou um array com as informações da transação caso a transação seja concluída com sucesso.

Caso o retorno seja false você pode acessar o último erro através do **$this->Cielo->erro['mensagem']**.


**Importante!!** Redirecionamentos nos casos de BuyPage Cielo ou Autenticação Verified By Visa ou Secure Code (utilizadas na BuyPage Loja) são afetuados automaticamente pelo método **finalizar()** porém nesse caso se torna fundamental a utilização do método **retornoTransacao()** para acessar as informações da transação criada.


Abaixo alguns exemplos:

###Criando uma transação BuyPage Cielo

```php
<?php
/* No PedidosController.php */
if($this->request->is('post')) {
    $this->Cielo->pedido_id = 20;
    $this->Cielo->cc_bandeira = 'visa';
    $this->Cielo->cc_produto = 2; #parcelas pela loja
    $this->Cielo->autorizar = 1; #autorizar somente se autenticada
    $this->Cielo->capturar = false; #captura automática
    $this->Cielo->pedido_valor = $this->Cielo->converterValor(250.25);
    $this->Cielo->pedido_data_hora = $this->Cielo->converterData('2012-09-03 20:15:16');
    $this->Cielo->pagamento_qtd_parcelas = 2;
    $this->Cielo->url_retorno = Router::url(array('action' => 'retorno_cielo', 20), true);

    $this->Cielo->finalizar();
}

$retorno_cielo = $this->Cielo->retornoTransacao();
```
    
###Criando uma transação BuyPage Loja

```php
<?php
/* No PedidosController.php */
if($this->request->is('post')) {
    $this->Cielo->pedido_id = 20;
    $this->Cielo->cc_numero = 123457812345678;
    $this->Cielo->cc_validade = 201805;
    $this->Cielo->cc_codigo_seguranca = 123;
    $this->Cielo->cc_bandeira = 'visa';
    $this->Cielo->cc_produto = 2; #parcelas pela loja
    $this->Cielo->autorizar = 1; #autorizar somente se autenticada
    $this->Cielo->capturar = false; #captura automática
    $this->Cielo->pedido_valor = $this->Cielo->converterValor(250.25);
    $this->Cielo->pedido_data_hora = $this->Cielo->converterData('2012-09-03 20:15:16');
    $this->Cielo->pagamento_qtd_parcelas = 2;
    $this->Cielo->url_retorno = Router::url(array('action' => 'retorno_cielo', 20), true);

    $this->Cielo->finalizar();
}

$retorno_cielo = $this->Cielo->retornoTransacao();
```

###Consultando uma transação

```php
<?php
$cielo_transacao_id = '100699306914AC581001';
$consulta = $this->Cielo->consultarTransacao($cielo_transacao_id);

if($consulta) {
    $this->set(compact('consulta'));
} else {
    $erro = $this->Cielo->erro['mensagem'];
    $this->Session->setFlash($erro);
}

$this->redirect($this->referer());
```

###Cancelando uma transação

```php
<?php
$cielo_transacao_id = '100699306914AC581001';
$cancelamento = $this->Cielo->cancelarTransacao($cielo_transacao_id);

if($cancelamento) {
    $this->Session->setFlash('Transação cancelada com sucesso');
} else {
    $erro = $this->Cielo->erro['mensagem'];
    $this->Session->setFlash($erro);
}

$this->redirect($this->referer());
```
    
###Capturando uma transação
Você pode capturar valores parciais passando o valor no formato padrão do webservice da Cielo através do segundo parâmetro usando o método **capturarTransacao()**. Caso o valor a ser capturado seja omitido o valor total irá ser capturado.

```php
<?php
$cielo_transacao_id = '100699306914AC581001';
$valor_a_ser_capturado = $this->Cielo->converterValor(250.25);
$captura = $this->Cielo->capturarTransacao($cielo_transacao_id, $valor_a_ser_capturado);

if($captura) {
    $this->Session->setFlash('Transação capturada com sucesso');
} else {
    $erro = $this->Cielo->erro['mensagem'];
    $this->Session->setFlash($erro);
}

$this->redirect($this->referer());
```

###Retornos
Os retornos das operações, quando feitos com sucesso, são basicamente os XML's retornados pelo webservice convertidos em array, então sugiro ver os XML's de exemplo contido no Kit Cielo eCommerce.

###Métodos auxiliares
Por padrão o webservice da Cielo trabalha com valores sem vírgulas ou pontos, ou seja, R$25,90 seria 2590, para isso criei o método **converterValor(float $valor);**

Datas devem ser enviadas na criação da transação da seguinte forma YYYY-MM-DDTHH-MM-SS (Exemplo: 2012-09-10T18:02:01), para isso há o método **converterData(string $data);** que converte do formato YYYY-MM-DD HH:MM:SS (Exemplo: 2012-05-07 08:53:54) para o esperado pelo webservice.


##Licença

Licenciado sob a licença MIT, que em resumo destaca:

 - O uso do plugin é livre e gratuíto, em qualquer situação
 - É livre modificação e redistribuição do código
 - Use o código por sua conta e risco
 - Os autores e colaboradores não fornecem nenhum tipo de garantia explícita
 ou implícita


The MIT License

Copyright 2012, Samuel Simões

Permission is hereby granted, free of charge, to any person obtaining a
copy of this software and associated documentation files (the "Software"),
to deal in the Software without restriction, including without limitation
the rights to use, copy, modify, merge, publish, distribute, sublicense,
and/or sell copies of the Software, and to permit persons to whom the
Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
DEALINGS IN THE SOFTWARE.

-----------------------------------------

**Samuel Simões ~ @samuelsimoes**
