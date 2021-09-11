<?php

//header('Content-Type: application/json; charset=utf-8');

if (!isset($_POST) || empty($_POST['id'])) {
    die("Post fail!");
}

require_once 'config.php';
require_once 'func.php';

$data = connect(HOST,USER,PASSWORD,DATABASE);
if (!$data) {
    die("Connection fail!");
}

// Dados do pagamento
$data_payment = executeData($data,'SELECT * FROM pedidos_pagamentos WHERE id = '.$_POST["id"]);
$data_payment = $data_payment->fetch();

// Dados do pedido
$data_order = executeData($data,'SELECT * FROM pedidos WHERE id = '.$data_payment["id_pedido"]);
$data_order = $data_order->fetch();

// Dados do cliente
$data_customer = executeData($data,'SELECT * FROM clientes WHERE id = '.$data_order["id_cliente"]);
$data_customer = $data_customer->fetch();

$fields = array(
    'external_order_id' => $data_payment['id_pedido'],
    'amount' => ($data_order['valor_total']+$data_order['valor_frete']),
    'card_number' => $data_payment['num_cartao'],
    'card_cvv' => strval($data_payment['codigo_verificacao']),
    'card_expiration_date' => date("my", strtotime($data_payment['vencimento'])),
    'card_holder_name' => $data_payment['nome_portador'],
    'customer' => array(
        'external_id' => $data_customer['id'],
        'name' => $data_customer['nome'],
        'type' => ($data_customer['tipo_pessoa'] == 'F' ? 'individual' : 'corporation'),
        'email' => $data_customer['email'],
        'documents' => [
            'type' => 'cpf',
            'number' => $data_customer['cpf_cnpj']
        ],
        'birthday' => $data_customer['data_nasc']
    )
);



// Realiza a consulta a API
$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, 'https://api11.ecompleto.com.br/exams/processTransaction');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

$headers = array();
$headers[] = 'Accept: application/json';
$headers[] = 'Authorization: 2737b2c5db750b412ec92b74fe8eb924';
$headers[] = 'Content-Type: application/x-www-form-urlencoded';
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$result = curl_exec($ch);
if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
    return;
}
curl_close($ch);

$dataCurl = json_decode($result);
if (!$dataCurl->Error) {
    switch ($dataCurl->Transaction_code) {
        case '00':
            $id_situation = 2; break;
        case '01':
            $id_situation = 1; break;
        case '02':
            $id_situation = 1; break;
        case '03':
            $id_situation = 3; break;
        case '04':
            $id_situation = 3; break;
    }

    // Atualiza pedidos_pagamentos
    $updatePayment = executeData($data,'UPDATE pedidos_pagamentos SET retorno_intermediador = \''.$result.'\', data_processamento = \''.date('Y-m-d').'\' WHERE id = '.$data_payment['id']);
    if (!$updatePayment) {
        return false;
    }

    // Atualiza pedidos
    $updateOrder = executeData($data,'UPDATE pedidos SET id_situacao = '.$id_situation.' WHERE id = '.$data_order["id"]);
    if ($updateOrder) {
        print_r(($result));
    }
}
return false;

?>