<?php

class ExportAccounts extends Module
{
	
	public $fieldlist=array(
		0=>'"0"',
		1=>'"HUPI"',
		
	);
	
	public $fieldnames=array(
	0=>'date_invoice_start',
	1=>'date_invoice_end',
	);

	function __construct()
	{
		$this->name = 'exportaccounts';
		$this->tab = 'Tools';
		$this->version = '1.2';
		
		/* The parent construct is required for translations */
		parent::__construct();
		
		$this->page = basename(__FILE__, '.php');
		$this->displayName = $this->l('Export Accounts');
		$this->description = $this->l('A module to export Ciel invoices format.');
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
			/*analyze de la reception des infos passÈes dans l'URL*/
			$fields=array();
			$titles=array();
			$invoice_start_date=$_POST['invoice_start_date'];
			$invoice_end_date=$_POST['invoice_end_date'];
			
			$list_id_orders = Order::getOrdersIdInvoiceByDate($_POST['invoice_start_date'], $_POST['invoice_end_date'], NULL, 'invoice');
			/*$orders = Order::getOrdersIdInvoiceByDate('2010-09-10','2010-12-11', NULL, 'invoice');*/
			/*$my_order = new Order(150);
			echo "ma commande".$my_order->invoice_number;*/
			
			if (!$orders) 
				echo "Exportation des factures pour Ciel terminée";
			else
				echo "Facture du ".$invoice_start_date.' au '.$invoice_end_date;
				{
					$f=fopen(dirname(__FILE__).'/HUPI_CIEL_'.date("Ymd").'.txt', 'w'); /* fichiers format Ciel*/
					$cpt_mvt=1; /* compteur pour les mouvements*/
					foreach ($list_id_orders as $my_id_order)
						{
							$order = new Order($my_id_order); 
							$customer = new Customer($order->id_customer);
							if ( $order->valid == 1 ) {
								/*Partie GÈnÈration Vente uniquement: Journal VT*/
								$arr_compte=array('411100','707000','708500','445714');
								foreach ($arr_compte as $local_compte)
								{
									$mouvement=$cpt_mvt.';VT;'.strftime("%Y%m%d",strtotime($order->invoice_date)).';'.strftime("%Y%m%d",strtotime($order->invoice_date)).';'.str_pad($order->invoice_number,6,"0", STR_PAD_LEFT).';'.$local_compte.';'.utf8_decode($customer->lastname.' '.$customer->firstname).';';
									switch ($local_compte) {
									case '411100':
										$mouvement.=$order->total_paid.';'.'D;'.str_pad($order->invoice_number,6,"0", STR_PAD_LEFT).';'.'Clients e-commerce';
										break;
									case '707000':
										$mouvement.=$order->total_products.';'.'C;'.str_pad($order->invoice_number,6,"0", STR_PAD_LEFT).';'.'Ventes de marchandises';
										break;
									case '708500':
										/* cas des ports offerts, montant 0 non accepte par ciel */
										if ( $order->total_shipping > 0 ) {
										$mouvement.=round($order->total_shipping/1.196,2).';'.'C;'.str_pad($order->invoice_number,6,"0", STR_PAD_LEFT).';'.'Ports et frais accessoires fact.';
										}
										else
										{
										$mouvement="";
										}
										
										break;
									case '445714':
										/*$mouvement.=round((($order->total_products*0.196)+($order->total_shipping-($order->total_shipping/1.196))),2).';'.'C;'.str_pad($order->invoice_number,6,"0", STR_PAD_LEFT).';'.'Tva collectÈe 19.6 %';*/
                                        /*$mouvement.=round($order->total_shipping-($order->total_shipping/1.196),2)+round($order->total_products*0.196,2).';'.'C;'.str_pad($order->invoice_number,6,"0", STR_PAD_LEFT).';'.'Tva collectÈe 19.6 %';*/
                                        $mouvement.=$order->total_paid-($order->total_products+round($order->total_shipping/1.196,2)).';'.'C;'.str_pad($order->invoice_number,6,"0", STR_PAD_LEFT).';'.'Tva collectée 19.6 %';
										break;
									
									}
								if ($mouvement <> "")				
									fwrite($f, $mouvement."\n");
								}
							
								/*Partie GÈnÈration Paiement: Journal BQ*/
								$cpt_mvt++;
								$arr_compte=array('51200x','411100');
								foreach ($arr_compte as $local_compte)
								{
									$mouvement=$cpt_mvt.';BQ;'.strftime("%Y%m%d",strtotime($order->invoice_date)).';'.strftime("%Y%m%d",strtotime($order->invoice_date)).';'.str_pad($order->invoice_number,6,"0", STR_PAD_LEFT).';';
									switch ($local_compte) 
									{
									case '411100':
                                        $mouvement.=$local_compte.';'.utf8_decode($customer->lastname.' '.$customer->firstname).';'.$order->total_paid.';'.'C;'.str_pad($order->invoice_number,6,"0", STR_PAD_LEFT).';'.'Clients e-commerce';
                                            
										break;
									case '51200x':
										if ( $order->payment == "PayPal" ) {
											$mouvement.='512002;'.utf8_decode($customer->lastname.' '.$customer->firstname).';'.$order->total_paid.';'.'D;'.str_pad($order->invoice_number,6,"0", STR_PAD_LEFT).';'.'Banque Paypal';
										}
										else {
											$mouvement.='512001;'.utf8_decode($customer->lastname.' '.$customer->firstname).';'.$order->total_paid.';'.'D;'.str_pad($order->invoice_number,6,"0", STR_PAD_LEFT).';'.'Banque SG';
										}
										break;
									}
								fwrite($f, $mouvement."\n");	
								}
							
								$cpt_mvt++;
							}	
						} 
					fclose($f);	
				}
			
			$this->_html.=$this->_displayForm();
			return $this->_html;
			
		}else{ /*premier affichage */
			
			/* display the editorial's form */
			$this->_html.=$this->_displayForm();
			
			$this->_html.='<p>
			Created by HUPI - Vincent Moreno
			</p>';
			
			return $this->_html;
		}
	}

	private function _displayForm()
	{
		$form='<form method="post">';
		$form.='<table>';
		$form.='
				<tr>
					<td height="30">'.$this->l('Start/End invoices by date, you wish').'</td>
				</tr>';
		$form.='
				<tr>
					<td height="30">
						Enter start date:  <input type="text" name="invoice_start_date" id="1"  value="yyyy-mm-dd" />
					</td>&nbsp;
					<td height="30">
						Enter end date:  <input type="text" name="invoice_end_date" id="2" value="yyyy-mm-dd" />
					</td>
				</tr>';
		
		$form.='
				<tr>
					<td height="30"><input type="submit" name="submitFilter" value="'.$this->l('Generate Ciel file').'" /></td>
				</tr>';
		$form.='</table>';
		$form.='</form>';
		
		return $form;
	}

}
