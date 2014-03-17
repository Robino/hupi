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
					
					
					/*Choix du transporteur */
					$carrier = new Carrier($this_order->id_carrier);
					switch ( $carrier->name ) {
					
						case "Mondial Relay" :
							
							$isMDR = true;
							$my_carrier = "SPOT";
							
							/*RŽcupŽration du cu numŽro de point Relais dans le cas o le transporteur est mondial relay */
							
							$simpleresul = Db::getInstance()->executeS('
							SELECT `MR_Selected_Num` 
							FROM `'._DB_PREFIX_. '`mr_selected 
							WHERE id_cart='.(int)($this_order->id_cart));
							
							
							$mdr_sql = 'SELECT MR_Selected_Num FROM '._DB_PREFIX_.'mr_selected WHERE id_cart='.$this_order->id_cart;
								
							$resultMDR = Db::getInstance()->getRow($mdr_sql);
							$NbMDR = $resultMDR['MR_Selected_Num'];
									
						
							break;
						
						case "La Poste" :
							$my_carrier = "SUIS";
							$isMDR = false;
							
							break;
							
						default :
							$my_carrier = "ERROR";
						
					}
											
					foreach ($products as $my_order_product)
					{
						$my_ligne='0;HUPI;'.$my_order.';;'.$this_order->id_customer.';';
						/*$my_ligne.=utf8_decode($addressDelivery->company).' '.utf8_decode($addressDelivery->lastname).' '.utf8_decode($addressDelivery->firstname).';';*/
						/*$my_ligne.=utf8_decode($addressDelivery->address1.';'.$addressDelivery->address2.';;;'.$addressDelivery->postcode.';'.$addressDelivery->city.';');*/
                        $my_ligne.=$addressDelivery->company.' '.$addressDelivery->lastname.' '.$addressDelivery->firstname.';';
                        $my_ligne.=$addressDelivery->address1.';'.$addressDelivery->address2.';;;'.$addressDelivery->postcode.';'.$addressDelivery->city.';';
						$my_ligne.=$country_order->iso_code.';'.$addressDelivery->country.';';
                        if ( Pack::isPack($my_order_product['product_id']) ) /* Cas ou nous avons un pack*/
                        {
                            $packproducts = Pack::getItems($my_order_product['product_id'],$cookie->id_lang);
                            
                            foreach ( $packproducts as $mypackproducts)
                            {
                                $mysubproduct = new Product($mypackproducts->id);
                                $mysubproductdetails = $mysubproduct->getFields();
                                /*foreach($mysubproductdetails as $cle => $valeur) (fait pour lire un tableau)
                                {
                                    $my_ligne2.=$cle.' : '.$valeur.'<br/>';
                                }*/
                                
                                $my_ligne2=$my_ligne.';;;;;;;;;;;;;;;'.$my_carrier.';;;;;'.$mysubproductdetails['reference'].';'.$my_order_product['product_quantity'];
                                if ( $isMDR ) /* Ajout aprs la qt produit de la ligne pour l'envoi vers Mondial Relay */
                        			$my_ligne2.=';;;;'.$carrier->name.';'.$NbMDR;
                                fwrite($f,$my_ligne2."\n");
                            }
                        }
                        else /*cas sans pack*/
                        {
						$my_ligne.=';;;;;;;;;;;;;;;'.$my_carrier.';;;;;'.$my_order_product['product_name'].';'.$my_order_product['product_quantity'];
						if ( $isMDR ) /* Ajout aprs la qt produit de la ligne pour l'envoi vers Mondial Relay */
                        	$my_ligne.=';;;;'.$carrier->name.';'.$NbMDR;
						fwrite($f,$my_ligne."\n");
                        }
                        
                        
					}
					
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
