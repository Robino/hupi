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
			
			/* GÈnÈration de la ligne */
			
			foreach ($orders as $my_order)
			{
			$this_order = new Order($my_order);
			
				if ( $this_order->getCurrentState() == 2) /* on ne prend que les commandes avec un paiement effectuÈ comme dernier status*/
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
							
							/*Récupération du cu numéro de point Relais dans le cas où le transporteur est mondial relay */
							
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
                                if ( $isMDR ) /* Ajout après la qt produit de la ligne pour l'envoi vers Mondial Relay */
                        			$my_ligne2.=';;;;'.$carrier->name.';'.$NbMDR;
                        			
                        		$my_ligne2.=";".$customer->email.";".str_replace(' ','',$addressDelivery->phone_mobile).";;Mail";
                                fwrite($f,$my_ligne2."\n");
                            }
                        }
                        else /*cas sans pack*/
                        {
						$my_ligne.=';;;;;;;;;;;;;;;'.$my_carrier.';;;;;'.$my_order_product['product_reference'].';'.$my_order_product['product_quantity'];
						if ( $isMDR ) /* Ajout après la qt produit de la ligne pour l'envoi vers Mondial Relay */
                        	$my_ligne.=';;;;'.$carrier->name.';'.$NbMDR;
                        	
                        $my_ligne.=";".$customer->email.";".str_replace(' ','',$addressDelivery->phone_mobile).";;Mail";
						fwrite($f,$my_ligne."\n");
                        }
                        
                        
					}
					
				}
				
			}
		
			
			/*Tools::redirect('modules/exportorders2/HUPI_SEND_'.date("Ymd").'.txt');*/
		}else{
			if (isset($_POST['Download'])) //download generated report
			{
				//Tools::redirect('modules/exportorders2/HUPI_SEND_'.date("Ymd").'.txt');
			    if (! file_exists('../modules/exportorders2/HUPI_SEND_'.date("Ymd").'.txt'))
			    	{
			    		$this->_html.="Fichier non présent ou non généré";
			    		$this->_html.=$this->_displayForm();
			    		return $this->_html;
			    	}
			    $handle = fopen('../modules/exportorders2/HUPI_SEND_'.date("Ymd").'.txt', "r");
						
				$this->_html.='<table cellspacing="0" cellpadding="0" class="table" style="width: 410px">';
				while (($data_report = fgetcsv($handle,0,';')) !== FALSE) 
				{
						$this->_html.='<tr>';
						$num=count($data_report);
						for ($c=0 ; $c < $num ;$c++)
						{
							if ( ! empty($data_report[$c]) )
								$this->_html.='<td>'.$data_report[$c].'</td>';
						}
						$this->_html.='</tr>';
									
				}
				
				$this->_html.='</table>';
				fclose($handle);
				
			
			
			}else{
			
				if (isset($_POST['envoyer'])) //upload du fichier StockAZ suivi des commandes
				{
					$resultat = move_uploaded_file($_FILES['avatar']['tmp_name'],'../modules/exportorders2/Tracking/trackupdate_'.date("Ymd").'.csv');
					if ($resultat) $this->_html.= "Transfert réussi";
				
				}else{
				
					if (isset($_POST['Tracking']))
					{
						if (! file_exists('../modules/exportorders2/Tracking/trackupdate_'.date("Ymd").'.csv'))
			    		{
							$this->_html.="Fichier non présent ou non généré";
							$this->_html.=$this->_displayForm();
							return $this->_html;
			    		}
						$handle = fopen('../modules/exportorders2/Tracking/trackupdate_'.date("Ymd").'.csv', "r");
						
						$this->_html.='<table cellspacing="0" cellpadding="0" class="table" style="width: 429px">';
						while (($data = fgetcsv($handle,0,';')) !== FALSE) 
						{
    							$this->_html.='<tr>';
								$this->_html.='<td>'.$data[2].'</td>';
								$this->_html.='<td>'.$data[13].'</td>';
								$this->_html.='</tr>';
											
    					}
						$this->_html.='</table>';
						$this->_html.='<form method="post">';
						$this->_html.='<input type="submit" name="updateTracking" value="Update">';
						$this->_html.='</form>';
						fclose($handle); 
						
					}else{	
						if (isset($_POST['updateTracking']))
						{
							
							if (! file_exists('../modules/exportorders2/Tracking/trackupdate_'.date("Ymd").'.csv'))
			    			{
								$this->_html.="Fichier non présent ou non généré";
								$this->_html.=$this->_displayForm();
								return $this->_html;
			    			}
							$handle = fopen('../modules/exportorders2/Tracking/trackupdate_'.date("Ymd").'.csv', "r");
							$i=0;
							$this->_html.='<table>';
							while (($data = fgetcsv($handle,0,';')) !== FALSE) 
							{
								if ($i > 0) //sauter la ligne d entete
								{
									$this_order = new Order((int)$data[2]);
									$this_order->shipping_number=$data[13];
									$this_order->update();
									// Forcer pour mettre à "en cours de livraison" (user Vincent) + envoie du mail 
									//Bug d'envoi de mails si plusieurs lignes, envoi d'un mail par ligne de commande et non pas par commande
									$this_order->setCurrentState(4,3);

								}
								
								$i++;
											
    						}
							$this->_html.='</table>';
							fclose($handle);
						}
						
					
						}
					
					
					
							/* display the editorial's form */
							$this->_html.=$this->_displayForm();
							
							$this->_html.='<p>
							Created by HUPI / Vincent Moreno
							</p>';
							
							//return $this->_html;
						
				}
				
			}
		}
		
	if (isset($_POST['Send'])) //Envoi du mail de commandes avec les commandes du jour générées
	{
		$templateVars = array(
						'{commande}' => date("Ymd"),
						'{datecommande}' => date("d")."/".date("m")."/".date(Y)
					);
					
					
					
		$fileAttachment['content'] = file_get_contents('../modules/exportorders2/HUPI_SEND_'.date("Ymd").'.txt'); //File path
		$fileAttachment['name'] = 'HUPI_SEND_'.date("Ymd").'.txt'; //Attachment filename
		$fileAttachment['mime'] = 'text/plain'; //mime file type
		
		$to_partners = array('vincent.moreno@hupi.fr','jl.agoutborde@hupi.fr','commandes@stock-az.fr');
		
		@Mail::Send('2', 'stockaz', Mail::l('Commande du '.$templateVars['{datecommande}'], 2), $templateVars,
			$to_partners, 'Vincent MORENO (HUPI)', null,null,$fileAttachment);
	
		$this->_html="Mail envoyé";	
		$this->_html.=$this->_displayForm();
		return $this->_html;
	}
		
		$this->_html.=$this->_displayForm();
		return $this->_html;
	}

	private function _displayForm()
	{
		$form='<form method="post" enctype="multipart/form-data">';
		$form.='<table>';
		$form.='
				<tr>
					<td height="30">'.$this->l('Generate Order Report for StockAZ.').'</td>
				</tr>';
		
		$form.='
				<tr>
					<td height="30"><input type="submit" name="submitFilter" value="'.$this->l('Generate Report').'" /></td>
					<td height="30"><input type="submit" name="Download" value="'.$this->l('Control report generated today').'" /></td>
					<td height="30"><input type="submit" name="Send" value="'.$this->l('Send mail').'" /></td>
					<td height="30"><input type="submit" name="Tracking" value="'.$this->l('Delivery Tracking').'" /></td>
					<td height="30"><input type="file" name="avatar" id="avatar"/></td>
					<td height="30"><input type="submit" name="envoyer" value="Envoyer le fichier" value="Fichier Tracking">					
				</tr>';
		$form.='</table>';
		
		
		return $form;
	}
 

}
