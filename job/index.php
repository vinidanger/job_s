<?php
require_once 'config.php';
require_once 'func.php';

$data = connect(HOST,USER,PASSWORD,DATABASE);
if (!$data) {
    die("Connection fail!");
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta http-equiv="content-language" content="pt-br">
    
    <link rel="stylesheet" href="bs/css/bootstrap.css">
    <script src="bs/js/bootstrap.js"></script>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body style="background-color: #ececec;">
    <div class="container-fluid">
        <div class="container text-center" style="margin-top: 8rem;">
            <div class="bg-white shadow-sm p-3" style="border-radius: 1rem;">
                <strong>PEDIDOS AGUARDANDO PAGAMENTO</strong>
                <hr>

                <?
                // Clientes
                $customers = executeData($data,'SELECT * FROM clientes');
                $customers = $customers->fetchAll();
                
                // Lista os pedidos que estão aguardando confirmação do pagamento
                $order = executeData($data,'SELECT * FROM pedidos WHERE id_situacao = 1');
                if ($order && $order->rowCount() > 0) {
                    $order_payments = executeData($data,'SELECT * FROM pedidos_pagamentos WHERE id_formapagto = 3');
                    $order_payments = $order_payments->fetchAll();

                    $list_order_pay = array();
                    while ($row = $order->fetch(\PDO::FETCH_ASSOC)) {
                        $posPayment = array_search($row['id'], array_column($order_payments, 'id_pedido'));
                        if ($posPayment !== false) {
                            $row['id_payment'] = $order_payments[$posPayment]['id'];
                            array_push($list_order_pay,$row);
                        }
                    }
                    ?>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead class="table-light">
                                <th>Id</th>
                                <th>Cliente</th>
                                <th>CPF / CNPJ</th>
                                <th>Ação</th>
                            </thead>
                            <tbody>
                                <?
                                foreach ($list_order_pay as $value) {
                                    ?>
                                    <tr>
                                        <td><?=($value['id_payment']);?></td>
                                        <td><?=($customers[array_search($value['id_cliente'], array_column($customers, 'id'))]['nome']);?></td>
                                        <td><?=($customers[array_search($value['id_cliente'], array_column($customers, 'id'))]['cpf_cnpj']);?></td>
                                        <td>
                                            <button class="btn btn-primary" onclick="processPayment(<?=($value['id_payment']);?>)">
                                                Processar Pagamento
                                            </button>
                                        </td>
                                    </tr>
                                    <?
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    <?
                }
                else {
                    ?>
                    <div class="alert alert-info mt-2">
                        Não há pedidos registrados!
                    </div>
                    <?
                }
                ?>
            </div>
        </div>
    </div>
</body>
</html>

<?
include "includes/modalAlert.php";
?>

<script>
    function processPayment(id) {
        $.post("process_payment.php", {id: id}, function (data) {
            data = JSON.parse(data);
            console.log(data.toString());
            
            if (typeof(data) === "object") {
                if (!data['Error']) {
                    switch (data['Transaction_code']) {
                        case '00':
                            $('#modalBody').html('O pagamento foi aprovado!'); break;
                        case '01':
                            $('#modalBody').html('Pagamento em análise de crédito!'); break;
                        case '02':
                            $('#modalBody').html('Pagamento estornado!'); break;
                        case '03':
                            $('#modalBody').html('Pagamento Recusado. Alto risco de chargeback!'); break;
                        case '04':
                            $('#modalBody').html('Pagamento Recusado. Cartão sem crédito disponíve!'); break;
                    }
                    $('#modalBody').append('</br>Atualizando a página em 3 segundos...');
                    $("#modalAlert").modal("show");
                    
                    setTimeout(function(){ location.reload(); }, 3000);
                }
            }
        });
    }
</script>