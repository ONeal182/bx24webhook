<?
use Bitrix\Main\Loader;
class Bx24WbHook 
{
	
	public $web_hook; // вебхук б24
	public $company_id; // id компании создаём в б24
	public $arFields; // поля заказа полученные событием OnOrderAdd в init.php

	function __construct()
	{
		$this->web_hook = $web_hook;
		$this->arFields = $arFields;
		$this->company_id = $company_id;
	}

	public function creat_deal(){
		$name = $this->arFields['PROFILE_NAME'];
		$phone = $this->arFields['ORDER_PROP'][3];
		$email = $this->arFields['USER_EMAIL'];

		$queryUrl = $this->web_hook.'/crm.contact.add'; //Создаём контакт в B24
		$queryData = http_build_query(array(
			'fields' => array(
        // 'TITLE' => 'Название формы', 
				'NAME' => $name,
				'EMAIL' => array(
					array(
						'VALUE' => $email,
						'VALUE_TYPE' => 'WORK'
					)),
				'PHONE' => array(
					array(
						"VALUE" => $phone, 
						"VALUE_TYPE" => "WORK"
					)
				)
			),
			
			'params' => array("REGISTER_SONET_EVENT" => "Y")
		));

		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_SSL_VERIFYPEER => 0,
			CURLOPT_POST => 1,
			CURLOPT_HEADER => 0,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_URL => $queryUrl,
			CURLOPT_POSTFIELDS => $queryData,
		));

		$result = curl_exec($curl);
		$desc = '';

		curl_close($curl);
		$id = json_decode($result);

		$id_cont = $id->result; //Берём ID созданного контакта


		$queryUrl = $this->web_hook.'/crm.deal.add'; //Создаём сделку
		$queryData = http_build_query(array(
			'fields' => array(
				'TITLE' => 'Заказ с сайта № ' . $this->arFields['ID'],
				'COMPANY_ID'=> $this->company_id,
				'CONTACT_ID' => $id_cont,
				'COMMENTS'=> $this->arFields['USER_DESCRIPTION']



			)));

		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_SSL_VERIFYPEER => 0,
			CURLOPT_POST => 1,
			CURLOPT_HEADER => 0,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_URL => $queryUrl,
			CURLOPT_POSTFIELDS => $queryData,
		));

		$result = curl_exec($curl);
		$id_deal = json_decode($result);



		curl_close($curl);

		foreach ($this->arFields['BASKET_ITEMS'] as $key => $product) { // перебераем все товары из корзины

			$queryUrl = $this->web_hook.'/crm.product.list';
			$queryData = http_build_query(array(
				'filter' => array(
					'NAME' => $product['NAME']


				)));

			$curl = curl_init();
			curl_setopt_array($curl, array(
				CURLOPT_SSL_VERIFYPEER => 0,
				CURLOPT_POST => 1,
				CURLOPT_HEADER => 0,
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_URL => $queryUrl,
				CURLOPT_POSTFIELDS => $queryData,
			));

			$result = curl_exec($curl);
			$id_product = json_decode($result);
			$rows[] = array('PRODUCT_ID' => $id_product->result[0]->ID, 'QUANTITY'=>$product['QUANTITY'], "PRICE"=> $product['PRICE']); // Создаём массив объектов (товаров)



			curl_close($curl);


		}

		
		Loader::includeModule('sale'); //подключаем модуль корзины



		$deliveries = Bitrix\Sale\Delivery\Services\Table::getList( // получаем имя доставки по ID
			[
				'select' => ['NAME'],
				'filter' => ['ID' => $this->arFields['DELIVERY_ID'] ]
			]
		)->fetchAll();

		foreach($deliveries as $delivery)
		{
			$delivery_name = $delivery;


		}
		$rows[] = array('PRODUCT_NAME' => $delivery['NAME'], "PRICE"=> $this->arFields['PRICE_DELIVERY']); // добавляем имя доставки и её цену в объект товаров

		$queryUrl = $this->web_hook.'/crm.deal.productrows.set'; // добавляем товары в сделку
		$queryData = http_build_query(array(

			'id' => $id_deal->result,
			'rows' => $rows


		));

		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_SSL_VERIFYPEER => 0,
			CURLOPT_POST => 1,
			CURLOPT_HEADER => 0,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_URL => $queryUrl,
			CURLOPT_POSTFIELDS => $queryData,
		));

		$result = curl_exec($curl);


		curl_close($curl);

	}
}

