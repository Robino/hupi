<?php

class ExportOrders2 extends Module
{
	
	public $fieldlist=array(
		0=>'"0"',
		1=>'"HUPI"',
		2=>'O.`id_order`',
		3=>'""',
		4=>'C.`id_customer`',
		5=>'CONCAT(AD.`company`," ",AD.`lastname`," ",AD.`firstname`)',
		6=>'AD.`address1`',
		7=>'AD.`address2`',
		8=>'"a"',
		9=>'"b"',
		10=>'AD.`postcode`',
		11=>'AD.`city`',
		12=>'CO.`iso_code`',
		13=>'COL.`name`',
		14=>'"e"',
		15=>'"f"',
		16=>'"g"',
		17=>'"h"',
		18=>'"i"',
		19=>'"j"',
		20=>'"k"',
		21=>'"l"',
		22=>'"m"',
		23=>'"n"',
		24=>'"o"',
		25=>'"p"',
		26=>'"q"',
		27=>'"r"',
		28=>'"s"',
		29=>'"SUIS"',
		30=>'"t"',
		31=>'"u"',
		32=>'"v"',
		33=>'"w"',
		34=>'"MOLKKY"',
		35=>'OD.`product_quantity`',
		/*36=>'"x"'*/
	);
	
	
	function __construct()
	{
		$this->name = 'exportorders2';
		$this->tab = 'Tools';
		$this->version = '1.2';
		
		/* The parent construct is required for translations */
		parent::__construct();
		
		$this->page = basename(__FILE__, '.php');
		$this->displayName = $this->l('Export Orders');
		$this->description = $this->l('A module to export orders made on the page.');
	}

	function install()
	{
		if (!parent::install())
			return false;
		// Trunk file if already exists with contents
		/*
		if (!$fd = @fopen(dirname(__FILE__).'/editorial.xml', 'w'))
			return false;
		@fclose($fd);
		*/
	}

	
	function getContent()
	{
		/* display the module name */
		$this->_html = '<h2>'.$this->displayName.'</h2>';

		/* update the editorial xml */
		if (isset($_POST['submitFilter']))
		{
			$f=fopen(dirname(__FILE__).'/HUPI_SEND_'.date("Ymd").'.txt', 'w');
			/* Liste de toutes les commandes*/
			$result = Db::getInstance()->ExecuteS('
			SELECT `id_order`
			FROM `'._DB_PREFIX_.'orders`');

			$orders = array();
			foreach ($result AS $order)
			$orders[] = intval($order['id_order']);
			
			/* Génération de la ligne */
			
			foreach ($orders as $my_order)
			{
			$this_order = new Order($my_order);
			
				if ( $this_order->getCurrentState() == 2) /* on ne prend que les commandes avec un paiement effectué comme dernier status*/
				{
					
					$customer = new Customer($this_order->id_customer);
					$addressDelivery = new Address($this_order->id_address_delivery, intval($cookie->id_lang));
					$country_order = new Country($addressDelivery->id_country);
					$products = $this_order->getProducts();
					foreach ($products as $my_order_product)
					{
						$my_ligne='0;HUPI;'.$my_order.';;'.$this_order->id_customer.';';
						$my_ligne.=utf8_decode($addressDelivery->company).' '.utf8_decode($addressDelivery->lastname).' '.utf8_decode($addressDelivery->firstname).';';
						$my_ligne.=utf8_decode($addressDelivery->address1.';'.$addressDelivery->address2.';;;'.$addressDelivery->postcode.';'.$addressDelivery->city.';');
						$my_ligne.=$country_order->iso_code.';'.$addressDelivery->country.';';
						$my_ligne.=';;;;;;;;;;;;;;;SUIS;;;;;MOLKKY'.';'.$my_order_product['product_quantity'];
					}
					
					
					
					
					fwrite($f,$my_ligne."\n");
				}
				
			}
		
			
			/*Tools::redirect('modules/exportorders2/HUPI_SEND_'.date("Ymd").'.txt');*/
		}else{

			/* display the editorial's form */
			$this->_html.=$this->_displayForm();
			
			if (file_exists(dirname(__FILE__).'/orders.csv')){
				$this->_html.='<p><a href="../modules/exportorders2/orders.csv">'.$this->l('Download Last Report').'</a></p>';
				
			}
			
			$this->_html.='<p>
			Created by HUPI / Vincent Moreno
			</p>';
			
			return $this->_html;
		}
		$this->_html.=$this->_displayForm();
			return $this->_html;
	}

	private function _displayForm()
	{
		$form='<form method="post">';
		$form.='<table>';
		$form.='
				<tr>
					<td height="30">'.$this->l('Generate Order Report for StockAZ.').'</td>
				</tr>';
		
		$form.='
				<tr>
					<td height="30"><input type="submit" name="submitFilter" value="'.$this->l('Generate Report').'" /></td>
				</tr>';
		$form.='</table>';
		$form.='</form>';
		
		return $form;
	}

}
