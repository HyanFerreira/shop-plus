-- SOMENTE LEITURA. Execute com a conta leitora no schema de demonstração escolhido.
SELECT DATABASE() AS schema_atual, VERSION() AS mysql, CURRENT_USER() AS conta;
SHOW GRANTS;

-- Não selecionar senhas completas, tokens, recovery codes ou payload de sessão.
SELECT id, role, status, LEFT(password, 10) AS algoritmo FROM users ORDER BY id;
SELECT user_id, LEFT(cpf_encrypted, 20) AS cifra, LENGTH(cpf_hash) AS hmac_length FROM customer_profiles;
SELECT user_id, LEFT(number_encrypted, 20) AS cifra, LENGTH(number_hash) AS hmac_length FROM phones;
SELECT id, user_id, LEFT(street_encrypted, 20) AS cifra FROM addresses;

-- Local: o pedido ensaiado tem id7. Na VM, obtenha o ID do pedido recém-criado.
-- Troque o valor abaixo pelo ID da instância da apresentação.
SET @pedido_demo = 7;
SELECT id, user_id, public_number, status, subtotal_cents, shipping_cents, total_cents
FROM orders WHERE id = @pedido_demo;
SELECT order_id, product_id, product_name, quantity, unit_price_cents, total_cents
FROM order_items WHERE order_id = @pedido_demo;
SELECT o.id, o.subtotal_cents, SUM(i.quantity * i.unit_price_cents) AS itens_calculados,
       o.shipping_cents, o.total_cents, o.subtotal_cents + o.shipping_cents AS total_calculado
FROM orders o JOIN order_items i ON i.order_id = o.id WHERE o.id = @pedido_demo
GROUP BY o.id, o.subtotal_cents, o.shipping_cents, o.total_cents;
SELECT order_id, amount_cents, status, brand, last_four FROM payments WHERE order_id = @pedido_demo;
SHOW COLUMNS FROM payments; -- Comprovar ausência de CVV e PAN completo.
SELECT s.order_id, s.status, e.from_status, e.to_status, e.created_at
FROM shipments s JOIN shipment_events e ON e.shipment_id = s.id WHERE s.order_id = @pedido_demo;
SELECT p.id AS product_id, p.sku, v.on_hand, v.reserved, v.on_hand - v.reserved AS disponivel
FROM order_items i JOIN products p ON p.id = i.product_id
JOIN inventory_items v ON v.product_id = p.id WHERE i.order_id = @pedido_demo;
SELECT m.inventory_item_id, m.type, m.quantity_delta, m.reference_type, m.reference_id
FROM stock_movements m JOIN inventory_items v ON v.id = m.inventory_item_id
WHERE v.product_id IN (SELECT product_id FROM order_items WHERE order_id = @pedido_demo)
ORDER BY m.id;
SELECT COUNT(*) AS saldos_invalidos FROM inventory_items
WHERE on_hand < 0 OR reserved < 0 OR reserved > on_hand;
