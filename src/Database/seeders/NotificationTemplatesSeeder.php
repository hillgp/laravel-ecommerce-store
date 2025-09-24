<?php

namespace LaravelEcommerce\Store\Database\Seeders;

use LaravelEcommerce\Store\Models\NotificationTemplate;
use Illuminate\Database\Seeder;

class NotificationTemplatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            [
                'code' => 'order_confirmation',
                'type' => 'order',
                'channel' => 'email',
                'title' => 'Confirmação de Pedido - {{order_number}}',
                'content' => '
                    <h2>Olá {{customer_name}}!</h2>

                    <p>Seu pedido foi confirmado com sucesso!</p>

                    <div style="background-color: #f5f5f5; padding: 20px; margin: 20px 0;">
                        <h3>Detalhes do Pedido</h3>
                        <p><strong>Número do Pedido:</strong> {{order_number}}</p>
                        <p><strong>Total:</strong> R$ {{order_total}}</p>
                        <p><strong>Status:</strong> {{order_status}}</p>
                        <p><strong>Data:</strong> {{order_date}}</p>
                    </div>

                    <p>Agradecemos pela sua compra! Você receberá atualizações sobre o status do seu pedido.</p>

                    <p>Atenciosamente,<br>Equipe da Loja</p>
                ',
                'variables' => ['customer_name', 'order_number', 'order_total', 'order_status', 'order_date'],
                'locale' => 'pt-BR',
                'is_active' => true,
            ],
            [
                'code' => 'order_shipped',
                'type' => 'order',
                'channel' => 'email',
                'title' => 'Pedido Enviado - {{order_number}}',
                'content' => '
                    <h2>Olá {{customer_name}}!</h2>

                    <p>Seu pedido foi enviado!</p>

                    <div style="background-color: #e8f5e8; padding: 20px; margin: 20px 0;">
                        <h3>Informações de Entrega</h3>
                        <p><strong>Número do Pedido:</strong> {{order_number}}</p>
                        <p><strong>Código de Rastreamento:</strong> {{tracking_code}}</p>
                        <p><strong>Transportadora:</strong> {{shipping_carrier}}</p>
                        <p><strong>Previsão de Entrega:</strong> {{delivery_date}}</p>
                    </div>

                    <p>Você pode rastrear seu pedido usando o código acima no site da transportadora.</p>

                    <p>Atenciosamente,<br>Equipe da Loja</p>
                ',
                'variables' => ['customer_name', 'order_number', 'tracking_code', 'shipping_carrier', 'delivery_date'],
                'locale' => 'pt-BR',
                'is_active' => true,
            ],
            [
                'code' => 'order_delivered',
                'type' => 'order',
                'channel' => 'email',
                'title' => 'Pedido Entregue - {{order_number}}',
                'content' => '
                    <h2>Olá {{customer_name}}!</h2>

                    <p>Seu pedido foi entregue com sucesso!</p>

                    <div style="background-color: #e8f5e8; padding: 20px; margin: 20px 0;">
                        <h3>Confirmação de Entrega</h3>
                        <p><strong>Número do Pedido:</strong> {{order_number}}</p>
                        <p><strong>Data de Entrega:</strong> {{delivery_date}}</p>
                        <p><strong>Status:</strong> Entregue</p>
                    </div>

                    <p>Agradecemos pela sua preferência! Esperamos vê-lo novamente em breve.</p>

                    <p>Atenciosamente,<br>Equipe da Loja</p>
                ',
                'variables' => ['customer_name', 'order_number', 'delivery_date'],
                'locale' => 'pt-BR',
                'is_active' => true,
            ],
            [
                'code' => 'payment_reminder',
                'type' => 'payment',
                'channel' => 'email',
                'title' => 'Lembrete de Pagamento - {{order_number}}',
                'content' => '
                    <h2>Olá {{customer_name}}!</h2>

                    <p>Estamos aguardando o pagamento do seu pedido.</p>

                    <div style="background-color: #fff3cd; padding: 20px; margin: 20px 0;">
                        <h3>Detalhes do Pagamento</h3>
                        <p><strong>Número do Pedido:</strong> {{order_number}}</p>
                        <p><strong>Total:</strong> R$ {{order_total}}</p>
                        <p><strong>Vencimento:</strong> {{due_date}}</p>
                        <p><strong>Status:</strong> Aguardando Pagamento</p>
                    </div>

                    <p>Para evitar o cancelamento do pedido, efetue o pagamento até a data de vencimento.</p>

                    <p>Atenciosamente,<br>Equipe da Loja</p>
                ',
                'variables' => ['customer_name', 'order_number', 'order_total', 'due_date'],
                'locale' => 'pt-BR',
                'is_active' => true,
            ],
            [
                'code' => 'welcome_customer',
                'type' => 'customer',
                'channel' => 'email',
                'title' => 'Bem-vindo à Nossa Loja!',
                'content' => '
                    <h2>Olá {{customer_name}}!</h2>

                    <p>Seja bem-vindo à nossa loja online!</p>

                    <p>Estamos muito felizes em ter você como cliente. Aqui você encontrará os melhores produtos com qualidade e preços competitivos.</p>

                    <div style="background-color: #f8f9fa; padding: 20px; margin: 20px 0;">
                        <h3>Benefícios para Você:</h3>
                        <ul>
                            <li>Entrega rápida e segura</li>
                            <li>Suporte especializado</li>
                            <li>Promoções exclusivas</li>
                            <li>Programa de fidelidade</li>
                        </ul>
                    </div>

                    <p>Explore nossa loja e descubra tudo o que preparamos para você!</p>

                    <p>Atenciosamente,<br>Equipe da Loja</p>
                ',
                'variables' => ['customer_name'],
                'locale' => 'pt-BR',
                'is_active' => true,
            ],
            [
                'code' => 'password_reset',
                'type' => 'customer',
                'channel' => 'email',
                'title' => 'Redefinição de Senha',
                'content' => '
                    <h2>Olá {{customer_name}}!</h2>

                    <p>Recebemos uma solicitação para redefinir sua senha.</p>

                    <div style="background-color: #d1ecf1; padding: 20px; margin: 20px 0; text-align: center;">
                        <h3>Token de Redefinição</h3>
                        <p style="font-size: 24px; font-weight: bold; color: #0c5460;">{{reset_token}}</p>
                    </div>

                    <p>Use o token acima para redefinir sua senha. Este token expira em 24 horas.</p>

                    <p>Se você não solicitou esta redefinição, ignore este email.</p>

                    <p>Atenciosamente,<br>Equipe da Loja</p>
                ',
                'variables' => ['customer_name', 'reset_token'],
                'locale' => 'pt-BR',
                'is_active' => true,
            ],
            [
                'code' => 'low_stock_alert',
                'type' => 'inventory',
                'channel' => 'email',
                'title' => 'Alerta de Estoque Baixo',
                'content' => '
                    <h2>Alerta de Estoque</h2>

                    <p>Os seguintes produtos estão com estoque baixo:</p>

                    <div style="background-color: #f8d7da; padding: 20px; margin: 20px 0;">
                        <h3>Produtos com Estoque Baixo</h3>
                        {{products_list}}
                    </div>

                    <p>Verifique o estoque e considere fazer uma nova compra desses produtos.</p>

                    <p>Atenciosamente,<br>Sistema de Gestão</p>
                ',
                'variables' => ['products_list'],
                'locale' => 'pt-BR',
                'is_active' => true,
            ],
            [
                'code' => 'new_order_admin',
                'type' => 'order',
                'channel' => 'email',
                'title' => 'Novo Pedido Recebido - {{order_number}}',
                'content' => '
                    <h2>Novo Pedido Recebido</h2>

                    <div style="background-color: #d4edda; padding: 20px; margin: 20px 0;">
                        <h3>Detalhes do Pedido</h3>
                        <p><strong>Número:</strong> {{order_number}}</p>
                        <p><strong>Cliente:</strong> {{customer_name}}</p>
                        <p><strong>Email:</strong> {{customer_email}}</p>
                        <p><strong>Total:</strong> R$ {{order_total}}</p>
                        <p><strong>Itens:</strong> {{items_count}}</p>
                        <p><strong>Data:</strong> {{order_date}}</p>
                    </div>

                    <p>Acesse o painel administrativo para processar este pedido.</p>

                    <p>Atenciosamente,<br>Sistema da Loja</p>
                ',
                'variables' => ['order_number', 'customer_name', 'customer_email', 'order_total', 'items_count', 'order_date'],
                'locale' => 'pt-BR',
                'is_active' => true,
            ],
            [
                'code' => 'order_status_update',
                'type' => 'order',
                'channel' => 'email',
                'title' => 'Atualização de Status - {{order_number}}',
                'content' => '
                    <h2>Olá {{customer_name}}!</h2>

                    <p>Seu pedido teve uma atualização de status.</p>

                    <div style="background-color: #e2e3e5; padding: 20px; margin: 20px 0;">
                        <h3>Status do Pedido</h3>
                        <p><strong>Número do Pedido:</strong> {{order_number}}</p>
                        <p><strong>Novo Status:</strong> {{order_status}}</p>
                        <p><strong>Atualizado em:</strong> {{update_date}}</p>
                    </div>

                    <p>{{status_message}}</p>

                    <p>Atenciosamente,<br>Equipe da Loja</p>
                ',
                'variables' => ['customer_name', 'order_number', 'order_status', 'update_date', 'status_message'],
                'locale' => 'pt-BR',
                'is_active' => true,
            ],
            [
                'code' => 'newsletter',
                'type' => 'marketing',
                'channel' => 'email',
                'title' => '{{newsletter_subject}}',
                'content' => '
                    <h2>Olá {{customer_name}}!</h2>

                    <p>{{newsletter_content}}</p>

                    <p>Atenciosamente,<br>Equipe da Loja</p>
                ',
                'variables' => ['customer_name', 'newsletter_subject', 'newsletter_content'],
                'locale' => 'pt-BR',
                'is_active' => true,
            ],
        ];

        foreach ($templates as $template) {
            NotificationTemplate::updateOrCreate(
                ['code' => $template['code'], 'channel' => $template['channel']],
                $template
            );
        }
    }
}