
SET NAMES 'utf8';


 
INSERT INTO `users` ( `userlogin`, `userpass`, `createdon`, `email`, `acl`, `smartmenu`, `disabled`, `options`) VALUES( 'admin', '$2y$10$GsjC.thVpQAPMQMO6b4Ma.olbIFr2KMGFz12l5/wnmxI1PEqRDQf.', '2017-01-01', 'admin@admin.admin', '<detail><acltype>0</acltype><onlymy>0</onlymy><aclview></aclview><acledit></acledit><widgets></widgets><modules></modules></detail>', NULL, 0, 'a:0:{}');

 
INSERT  INTO `stores` (  `storename`, `description`) VALUES(  'Основной склад', '');
INSERT INTO `mfund` (`mf_id`, `mf_name`, `description`) VALUES(2, 'Касса', 'Основная касса');

INSERT INTO `options` (`optname`, `optvalue`) VALUES('common', 'a:18:{s:8:"defstore";s:2:"19";s:5:"defmf";s:1:"2";s:9:"qtydigits";s:1:"0";s:8:"amdigits";s:1:"0";s:11:"partiontype";s:1:"1";s:5:"cdoll";s:1:"2";s:5:"ceuro";s:1:"5";s:4:"crub";s:3:"0.4";s:6:"price1";s:18:"Розничная";s:6:"price2";s:14:"Оптовая";s:6:"price3";s:0:"";s:6:"price4";s:0:"";s:6:"price5";s:0:"";s:11:"autoarticle";b:1;s:6:"useset";b:0;s:10:"usesnumber";b:0;s:6:"useval";b:0;s:10:"usescanner";b:0;}');
INSERT INTO `options` (`optname`, `optvalue`) VALUES('firm', 'a:5:{s:8:"firmname";s:20:"Наша  фирма";s:5:"phone";s:0:"";s:5:"viber";s:0:"";s:7:"address";s:0:"";s:3:"inn";s:4:"1111";}');
 
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`, `smartmenu`) VALUES(1, 4, 'Склады', 'StoreList', 'Товары', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`, `smartmenu`) VALUES(2, 4, 'Номенклатура', 'ItemList', 'Товары', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`, `smartmenu`) VALUES(3, 4, 'Сотрудники', 'EmployeeList', '', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`, `smartmenu`) VALUES(4, 4, 'Категории товаров', 'CategoryList', 'Товары', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`, `smartmenu`) VALUES(5, 4, 'Контрагенты', 'CustomerList', '', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`, `smartmenu`) VALUES(6, 1, 'Приходная накладная', 'GoodsReceipt', 'Закупки', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`, `smartmenu`) VALUES(7, 1, 'Расходная накладная', 'GoodsIssue', 'Продажи', 0, 1);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`, `smartmenu`) VALUES(8, 3, 'Общий журнал', 'DocList', '', 0, 1);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`, `smartmenu`) VALUES(9, 5, 'Товары на складе', 'ItemList', '', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`, `smartmenu`) VALUES(10, 1, 'Гарантийный талон', 'Warranty', 'Продажи', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`, `smartmenu`) VALUES(11, 1, 'Перемещение товара', 'MoveItem', 'Склад', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`, `smartmenu`) VALUES(12, 2, 'Движение по складу', 'ItemActivity', 'Склад', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`, `smartmenu`) VALUES(13, 2, 'ABC анализ', 'ABC', '', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`, `smartmenu`) VALUES(14, 4, 'Услуги, работы', 'ServiceList', '', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`, `smartmenu`) VALUES(15, 1, 'Акт выполненных работ', 'ServiceAct', 'Продажи', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`, `smartmenu`) VALUES(16, 1, 'Возврат от покупателя', 'ReturnIssue', 'Продажи', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`, `smartmenu`) VALUES(18, 3, 'Работы, наряды', 'TaskList', '', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`, `smartmenu`) VALUES(19, 1, 'Наряд', 'Task', 'Производство', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`, `smartmenu`) VALUES(20, 2, 'Оплата по нарядам', 'EmpTask', 'Производство', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`, `smartmenu`) VALUES(21, 2, 'Закупки', 'Income', '', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`, `smartmenu`) VALUES(22, 2, 'Продажи', 'Outcome', '', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`, `smartmenu`) VALUES(46, 4, 'Денежные счета', 'MFList', '', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`, `smartmenu`) VALUES(27, 3, 'Заказы клиентов', 'OrderList', '', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`, `smartmenu`) VALUES(28, 1, 'Заказ', 'Order', 'Продажи', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`, `smartmenu`) VALUES(30, 1, 'Оприходование  с  производства', 'ProdReceipt', 'Производство', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`, `smartmenu`) VALUES(31, 1, 'Списание на  производство', 'ProdIssue', 'Производство', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`, `smartmenu`) VALUES(32, 2, 'Отчет по производству', 'Prod', 'Производство', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`, `smartmenu`) VALUES(33, 4, 'Производственные участвки', 'ProdAreaList', '', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`, `smartmenu`) VALUES(38, 1, 'Заявка  поставщику', 'OrderCust', 'Закупки', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`, `smartmenu`) VALUES(35, 3, 'Продажи', 'GIList', '', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`, `smartmenu`) VALUES(36, 4, 'Оборудование', 'EqList', '', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`, `smartmenu`) VALUES(37, 3, 'Закупки', 'GRList', '', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`, `smartmenu`) VALUES(39, 3, 'Заявки поставщикам', 'OrderCustList', '', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`, `smartmenu`) VALUES(40, 2, 'Прайс', 'Price', 'Склад', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`, `smartmenu`) VALUES(41, 1, 'Возврат поставщику', 'RetCustIssue', 'Закупки', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`, `smartmenu`) VALUES(43, 1, 'Заказ (услуги)', 'ServiceOrder', 'Продажи', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`, `smartmenu`) VALUES(44, 1, 'Перекомплектация ТМЦ', 'TransItem', 'Склад', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`, `smartmenu`) VALUES(45, 3, 'Производство', 'ProdList', '', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`, `smartmenu`) VALUES(47, 3, 'Журнал платежей', 'PayList', '', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`, `smartmenu`) VALUES(48, 2, 'Движение по денежным счетам', 'PayActivity', 'Платежи', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`, `smartmenu`) VALUES(49, 1, 'Перевод денежных средств', 'MoveMoney', 'Платежи', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`, `smartmenu`) VALUES(50, 1, 'Приходный ордер', 'IncomeMoney', 'Платежи', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`, `smartmenu`) VALUES(51, 1, 'Расходный ордер', 'OutcomeMoney', 'Платежи', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`, `smartmenu`) VALUES(52, 5, 'Расчеты с  контрагентами', 'PayCustList', '', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`, `smartmenu`) VALUES(53, 2, 'Платежный баланс', 'PayBalance', 'Платежи', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`, `smartmenu`) VALUES(57, 1, 'Инвентаризация', 'Inventory', 'Склад', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`, `smartmenu`) VALUES(58, 1, 'Счет входящий', 'InvoiceCust', 'Закупки', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`, `smartmenu`) VALUES(59, 1, 'Счет-фактура', 'Invoice', 'Продажи', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`, `smartmenu`) VALUES(60, 5, 'Импорт номенклатуры', 'Import', '', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`, `smartmenu`) VALUES(61, 3, 'Движение  ТМЦ', 'StockList', '', 0, 0);
INSERT INTO `metadata` (`meta_id`, `meta_type`, `description`, `meta_name`, `menugroup`, `disabled`, `smartmenu`) VALUES(62, 1, 'Кассовый чек', 'POSCheck', 'Продажи', 0, 0);
