<?php
require "vendor/autoload.php";
use phpseclib\Crypt\AES;
use phpseclib\Crypt\Random;

Class vatController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Xuất hóa đơn';

        $batdau = '01-'.date('m-Y');
        $ketthuc = date('t-m-Y');
        $this->view->data['batdau'] = $batdau;
        $this->view->data['ketthuc'] = $ketthuc;

        $customer_model = $this->model->get('customerModel');
        $customers = $customer_model->getAllCustomer(array('order_by'=>'customer_name ASC'));
        $this->view->data['customers'] = $customers;

        $invoice_model = $this->model->get('invoicetireModel');

        $last_num = '0000000';
        $last_date = strtotime(date('d-m-Y'));

        $invoices = $invoice_model->getAllInvoice(array('order_by'=>'invoice_tire_date DESC, invoice_tire_number DESC','limit'=>1));
        foreach ($invoices as $invoice) {
            $last_num = $invoice->invoice_tire_number;
            $last_date = $invoice->invoice_tire_date+86400;
        }
        $this->view->data['last_num'] = $last_num;
        $this->view->data['last_date'] = $last_date;

        $this->view->show('vat/index');
    }
    public function view() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Xuất hóa đơn';


        $this->view->show('vat/view');
    }
    public function getItemAdd() {
        if (isset($_GET['sohoadon'])) {
            $invoice_tire_detail_model = $this->model->get('invoicetiredetailModel');

            $invoices = $invoice_tire_detail_model->getAllInvoice(array('where'=>'invoice_tire_detail_number LIKE "%'.$_GET['sohoadon'].'%"','order_by'=>'invoice_tire_detail_date DESC'),array('table'=>'tire_brand,tire_size,tire_pattern','where'=>'invoice_tire_detail_brand=tire_brand_id AND invoice_tire_detail_size=tire_size_id AND invoice_tire_detail_pattern=tire_pattern_id'));
            
            $ngay = "";
            $thang = "";
            $nam = "";
            $sohd = "";
            $tennguoimua = "";
            $tendv = "";
            $mst = "";
            $diachi = "";
            $ten = array();
            $dvt = array();
            $sl = array();
            $dg = array();
            $tt = array();
            $price_hide = array();
            $total_hide = array();
            $brand = array();
            $size = array();
            $pattern = array();
            $order = array();
            $orderlist = array();

            $i=1;

            $ck = "";

            foreach ($invoices as $invoice) {
                if ($ck=="") {
                    $ck=$invoice->invoice_tire_detail_date;
                }
                if ($ck!="" && $ck==$invoice->invoice_tire_detail_date) {
                    $ngay = date('d',$invoice->invoice_tire_detail_date);
                    $thang = date('m',$invoice->invoice_tire_detail_date);
                    $nam = substr(date('Y',$invoice->invoice_tire_detail_date),2);
                    $sohd = $invoice->invoice_tire_detail_number;
                    $tennguoimua = $invoice->person_hide;
                    $tendv = $invoice->company_hide;
                    $mst = $invoice->mst_hide;
                    $diachi = $invoice->address_hide;
                    $ten[] = $invoice->tire_brand_name.' '.$invoice->tire_size_number.' '.$invoice->tire_pattern_name;
                    $dvt[] = substr($invoice->tire_size_number, -2)=='.5'?'Cái':'Bộ';
                    $sl[] = $invoice->invoice_tire_detail_volume;
                    $dg[] = $invoice->invoice_tire_detail_price;
                    $tt[] = round($invoice->total_hide);
                    $price_hide[] = $invoice->price_hide;
                    $total_hide[] = $invoice->total_hide;
                    $brand[] = $invoice->invoice_tire_detail_brand;
                    $size[] = $invoice->invoice_tire_detail_size;
                    $pattern[] = $invoice->invoice_tire_detail_pattern;
                    $order[] = $invoice->order_tire;
                    $orderlist[] = $invoice->order_tire_list;

                    

                    $i++;
                }
            }

            $result = array(
                'ngay'=>$ngay,
                'thang'=>$thang,
                'nam'=>$nam,
                'sohd'=>$sohd,
                'tennguoimua'=>$tennguoimua,
                'tendv'=>$tendv,
                'mst'=>$mst,
                'diachi'=>$diachi,
                'ten'=>$ten,
                'dvt'=>$dvt,
                'sl'=>$sl,
                'dg'=>$dg,
                'tt'=>$tt,
                'price_hide'=>$price_hide,
                'total_hide'=>$total_hide,
                'brand'=>$brand,
                'size'=>$size,
                'pattern'=>$pattern,
                'order'=>$order,
                'orderlist'=>$orderlist,
                'total'=>$i,
            );

            echo json_encode($result);
        }
    }
    public function printpage() {
        $this->view->disableLayout();
        $this->view->data['lib'] = $this->lib;

        $order_tire_list_model = $this->model->get('ordertirelistModel');

        $invoice_tire_model = $this->model->get('invoicetireModel');
        $invoice_tire_detail_model = $this->model->get('invoicetiredetailModel');

        $query  = explode('&', $_SERVER['QUERY_STRING']);
        $params = array();

        foreach( $query as $param )
        {
          list($name, $value) = explode('=', $param, 2);
          $params[urldecode($name)][] = urldecode($value);
        }
        
        $items = $params;

        
        foreach ($items['order'] as $value) {
            if($value>0){
                $data_invoice = array(
                    'order_tire'=>$value,
                    'invoice_tire_number'=>$items['sohd'][0],
                    'invoice_tire_date'=> strtotime($items['ngay'][0].'-'.$items['thang'][0].'-20'.$items['nam'][0]),
                    'invoice_tire_create_user'=>$_SESSION['userid_logined'],
                );
                $invoices = $invoice_tire_model->getInvoiceByWhere(array('order_tire'=>$data_invoice['order_tire'],'invoice_tire_number'=>$data_invoice['invoice_tire_number']));
                if (!$invoices) {
                    $invoice_tire_model->createInvoice($data_invoice);
                }
                else{
                    $invoice_tire_model->updateInvoice($data_invoice,array('invoice_tire_id'=>$invoices->invoice_tire_id));
                }
            }
        }

        
        $j=0;
        foreach ($items['orderlist'] as $value) {
            if($value>0){

                if(!isset($conlai[$items['brand'][$j]][$items['size'][$j]][$items['pattern'][$j]])){
                    $conlai[$items['brand'][$j]][$items['size'][$j]][$items['pattern'][$j]] = $items['sl'][$j];
                    $dongia[$items['brand'][$j]][$items['size'][$j]][$items['pattern'][$j]] = str_replace(',', '', $items['dg'][$j]);
                    $giavat[$items['brand'][$j]][$items['size'][$j]][$items['pattern'][$j]] = round(str_replace(',', '', $items['dg'][$j])*0.1);

                    $dongia_an[$items['brand'][$j]][$items['size'][$j]][$items['pattern'][$j]] = $items['price_hide'][$j];
                    $tt_an[$items['brand'][$j]][$items['size'][$j]][$items['pattern'][$j]] = $items['total_hide'][$j];
                }
                $sl = $conlai[$items['brand'][$j]][$items['size'][$j]][$items['pattern'][$j]];

                $o_list = $order_tire_list_model->getTire($value);
                if ($sl>$o_list->tire_number) {
                    $conlai[$items['brand'][$j]][$items['size'][$j]][$items['pattern'][$j]] -= $o_list->tire_number;
                    $sl = $o_list->tire_number;
                }

                $data_invoice = array(
                    'order_tire_list'=>$value,
                    'invoice_tire_detail_number'=>$items['sohd'][0],
                    'invoice_tire_detail_date'=> strtotime($items['ngay'][0].'-'.$items['thang'][0].'-20'.$items['nam'][0]),
                    'invoice_tire_detail_brand'=>$items['brand'][$j],
                    'invoice_tire_detail_size'=>$items['size'][$j],
                    'invoice_tire_detail_pattern'=>$items['pattern'][$j],
                    'invoice_tire_detail_volume'=>$sl,
                    'invoice_tire_detail_price'=>$dongia[$items['brand'][$j]][$items['size'][$j]][$items['pattern'][$j]],
                    'invoice_tire_detail_create_user'=>$_SESSION['userid_logined'],
                    'invoice_tire_detail_vat'=>$giavat[$items['brand'][$j]][$items['size'][$j]][$items['pattern'][$j]],
                    'order_tire'=>$items['order'][$j],
                    'person_hide'=>$items['tennguoimua'][0],
                    'company_hide'=>$items['tendv'][0],
                    'mst_hide'=>$items['mst'][0],
                    'address_hide'=>$items['diachi'][0],
                    'price_hide'=>$dongia_an[$items['brand'][$j]][$items['size'][$j]][$items['pattern'][$j]],
                    'total_hide'=>$tt_an[$items['brand'][$j]][$items['size'][$j]][$items['pattern'][$j]],
                );

                $invoices = $invoice_tire_detail_model->getInvoiceByWhere(array('order_tire'=>$data_invoice['order_tire'],'order_tire_list'=>$data_invoice['order_tire_list'],'invoice_tire_detail_number'=>$data_invoice['invoice_tire_detail_number']));
                if (!$invoices) {
                    $invoice_tire_detail_model->createInvoice($data_invoice);
                }
                else{
                    $invoice_tire_detail_model->updateInvoice($data_invoice,array('invoice_tire_detail_id'=>$invoices->invoice_tire_detail_id));
                }

                
            }

            $j++;
        }
        

        $this->view->data['items'] = $items;
        $this->view->data['nguoimh'] = $params['tennguoimua'][0];
        $this->view->data['company_name'] = $params['tendv'][0];
        $this->view->data['mst'] = $params['mst'][0];
        $this->view->data['customer_address'] = $params['diachi'][0];
        $this->view->data['thanhtoan'] = $params['thanhtoan'][0];
        $this->view->data['ngay'] = $params['ngay'][0];
        $this->view->data['thang'] = $params['thang'][0];
        $this->view->data['nam'] = $params['nam'][0];
        $this->view->data['sohd'] = $params['sohd'][0];
        $this->view->data['congtien'] = str_replace(',', '', $params['congtien'][0]);
        $this->view->data['tienthue'] = str_replace(',', '', $params['tienthue'][0]);
        $this->view->data['tongcong'] = str_replace(',', '', $params['tongcong'][0]);

        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
        $filename = "action_logs.txt";
        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$params['sohd'][0]."|invoice_tire|".$params['sohd'][0]."\n"."\r\n";
        
        $fh = fopen($filename, "a") or die("Could not open log file.");
        fwrite($fh, $text) or die("Could not write file!");
        fclose($fh);

        $this->view->show('vat/printpage');
    }
    public function printview() {
        $this->view->disableLayout();
        $this->view->data['lib'] = $this->lib;

        $query  = explode('&', $_SERVER['QUERY_STRING']);
        $params = array();

        foreach( $query as $param )
        {
          list($name, $value) = explode('=', $param, 2);
          $params[urldecode($name)][] = urldecode($value);
        }
        
        $items = $params;

        

        $this->view->data['items'] = $items;
        $this->view->data['nguoimh'] = $params['tennguoimua'][0];
        $this->view->data['company_name'] = $params['tendv'][0];
        $this->view->data['mst'] = $params['mst'][0];
        $this->view->data['customer_address'] = $params['diachi'][0];
        $this->view->data['thanhtoan'] = $params['thanhtoan'][0];
        $this->view->data['ngay'] = $params['ngay'][0];
        $this->view->data['thang'] = $params['thang'][0];
        $this->view->data['nam'] = $params['nam'][0];
        $this->view->data['sohd'] = $params['sohd'][0];
        $this->view->data['congtien'] = str_replace(',', '', $params['congtien'][0]);
        $this->view->data['tienthue'] = str_replace(',', '', $params['tienthue'][0]);
        $this->view->data['tongcong'] = str_replace(',', '', $params['tongcong'][0]);

        $this->view->show('vat/printview');
    }

   public function getItem(){
        $invoice_tire_detail_model = $this->model->get('invoicetiredetailModel');
        $items_model = $this->model->get('ordertireModel');
        $item_list_model = $this->model->get('ordertirelistModel');
        $batdau = $_GET['batdau'];
        $ketthuc = $_GET['ketthuc'];
        $customer = $_GET['customer'];

        if ($customer>0) {
            $items = $items_model->getAllTire(array('where'=>'customer = '.$customer.' AND ((delivery_date >= '.strtotime($batdau).' AND delivery_date <= '.strtotime($ketthuc).') OR (order_tire_status IS NULL OR order_tire_status=0)) AND vat>0','order_by'=>'order_number ASC'),array('table'=>'customer','where'=>'customer=customer_id'));
        }
        else{
            $items = $items_model->getAllTire(array('where'=>'((delivery_date >= '.strtotime($batdau).' AND delivery_date <= '.strtotime($ketthuc).') OR (order_tire_status IS NULL OR order_tire_status=0)) AND vat>0','order_by'=>'order_number ASC'),array('table'=>'customer','where'=>'customer=customer_id'));
        }
        
        
        $str = '<table class="table_data" id="tblExport2">';
        $str .= '<thead><tr><th class="fix"><input type="checkbox" onclick="checkall(\'checkbox2\', this)" name="checkall"/></th><th class="fix">Ngày</th><th class="fix">Số ĐH</th><th class="fix">KH</th><th class="fix">SL</th><th class="fix">Thu</th><th class="fix">Thuế</th><th class="fix">Tổng cộng</th></tr></thead>';
        $str .= '<tbody>';

        foreach ($items as $item) {
            $lists = $item_list_model->getAllTire(array('where'=>'order_tire='.$item->order_tire_id),array('table'=>'tire_brand,tire_size,tire_pattern','where'=>'tire_brand=tire_brand_id AND tire_size=tire_size_id AND tire_pattern=tire_pattern_id'));

            $str .= '<tr style="font-weight:bold" class="tr" data="'.$item->order_tire_id.'"><td><input name="check[]" type="checkbox" class="checkbox" value="'.$item->order_tire_id.'" data="'.$item->order_tire_id.'"></td><td class="fix">'.$this->lib->hien_thi_ngay_thang($item->delivery_date).'</td><td class="fix">'.$item->order_number.'</td><td class="fix">'.$item->customer_name.'</td><td class="fix">'.$item->order_tire_number.'</td><td class="fix">'.$this->lib->formatMoney($item->total-$item->vat+$item->discount+$item->reduce).'</td><td class="fix">'.$this->lib->formatMoney($item->vat).'</td><td class="fix">'.$this->lib->formatMoney($item->total).'</td></tr>';

            $customer = $item->customer_name;
            $company = $item->company_name;
            $mst = $item->mst;
            $dc = $item->customer_address;

            foreach ($lists as $order) {
                

                $details = $invoice_tire_detail_model->getAllInvoice(array('where'=>'order_tire_list='.$order->order_tire_list_id));
                $sum_vat = 0;
                foreach ($details as $detail) {
                    $sum_vat += $detail->invoice_tire_detail_volume;
                }

                if ($order->tire_number>$sum_vat) {

                    $ten = $order->tire_brand_name.' '.$order->tire_size_number.' '.$order->tire_pattern_name;
                    $dvt = substr($order->tire_size_number, -2)=='.5'?'Cái':'Bộ';
                    $sl = $order->tire_number-$sum_vat;
                    $dg = $item->check_price_vat==1?$order->tire_price_vat*$item->vat_percent*0.1/1.1:$order->tire_price*$item->vat_percent*0.1;
                    $tt = round($dg*$sl);
                    $congtien = $dg*$sl;
                    $dg1 = $dg;
                    $dg = round($dg);

                    $str .= '<tr style="font-style:italic" class="tr" data="'.$item->order_tire_id.'"><td><input name="check_i[]" type="checkbox" class="checkbox2" value="'.$order->order_tire_list_id.'" data="'.$item->order_tire_id.'" data-cus="'.$company.'" data-add="'.$dc.'" data-mst="'.$mst.'" data-ten="'.$ten.'" data-dvt="'.$dvt.'" data-sl="'.$sl.'" data-dg="'.$dg.'" data-dg1="'.$dg1.'" data-tt="'.$tt.'" data-cong="'.$congtien.'" data-brand="'.$order->tire_brand.'" data-size="'.$order->tire_size.'" data-pattern="'.$order->tire_pattern.'"></td><td class="fix"></td><td class="fix">'.$item->order_number.'</td><td class="fix">'.$ten.'</td><td class="fix">'.$sl.'</td><td class="fix">'.$this->lib->formatMoney($dg).'</td><td class="fix"></td><td class="fix">'.$customer.'</td></tr>';
                }
            }
            
        }
        
        $str .= '</tbody></table>';
        echo $str;
   }

   public function getMoney(){
        echo $this->lib->convert_number_to_words($_GET['data']).' đồng';
   }

   public function delete(){
        if (isset($_POST['data'])) {
            $invoice_tire_model = $this->model->get('invoicetireModel');
            $invoice_tire_detail_model = $this->model->get('invoicetiredetailModel');

            $invoice_tire_model->queryInvoice('DELETE FROM invoice_tire WHERE invoice_tire_number = "'.$_POST['data'].'"');
            $invoice_tire_detail_model->queryInvoice('DELETE FROM invoice_tire_detail WHERE invoice_tire_detail_number = "'.$_POST['data'].'"');

            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
            $filename = "action_logs.txt";
            $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|invoice_tire|".$_POST['data']."\n"."\r\n";
            
            $fh = fopen($filename, "a") or die("Could not open log file.");
            fwrite($fh, $text) or die("Could not write file!");
            fclose($fh);
        }
   }

   public function createEHoaDon(){ 
        $BkavPartnerGUID = "68D8219C-9AFB-43A1-9A93-7824D5949841";
        $BkavPartnerToken = "8VfXSP8h2GYZsOujKAxxXOjrwrplclW8U+GglMrw6mU=:slmm5fyrdZDQASy7hjQ33g==";
        $Mode = 6;
        $CmdType = 100; //Tạo HĐ, eHD tự cấp InvoiceForm, InvoiceSerial; InvoiceNo = 0 (tạo HĐ mới)
        $items = array();
        $EncryptedCommandData = $this->RemoteCommand($BkavPartnerToken, $Mode, $CmdType, $items);
        
        $params = array(
            'partnerGUID' => $BkavPartnerGUID,
            'CommandData' => $EncryptedCommandData
        );

        $webServiceClient = $this->connectBKAV();
        $response = $webServiceClient->__soapCall('ExecCommand', array('parameters' => $params));

        echo $response->ExecCommandResult;
   }

   public function connectBKAV(){
        $wsdlAddress = "https://wsdemo.ehoadon.vn/WSPublicEHoaDon.asmx?WSDL";
        
        $options = array(
            "soap_version" => SOAP_1_2,
            "cache_wsdl" => WSDL_CACHE_NONE,
            "trace" => 1,
            "exceptions" => true
        );

        $webServiceClient = new SoapClient($wsdlAddress, $options);

        return $webServiceClient;
   }
   public function RemoteCommand($BkavPartnerToken, $Mode = 6, $CmdType, $items = array()){
        $CommandData = array(
            'CommandType' => null,
            'CommandObject' => null
        );

        $CommandObject[] = array(
            'Invoice' => array(
                'InvoiceTypeID' => 1,
                'InvoiceDate' => date('m/d/Y h:i:s a', time()),
                'BuyerName' => "Nguyễn Văn A Update",
                'BuyerTaxCode' => "0104746603",
                'BuyerUnitName' => "Công Ty Luật TNHH ABC",
                'BuyerAddress' => "Nhà N2D Khu ĐT Trung Hoà-Nhân Chính, Phường Nhân Chính, Quận Thanh Xuân, Hà Nội",
                'BuyerBankAccount' => "",
                'PayMethodID' => 1,
                'ReceiveTypeID' => 3,
                'ReceiverEmail' => "ngoton007@yahoo.com",
                'ReceiverMobile' => "0902085911",
                'ReceiverAddress' => "Nhà N2D Khu ĐT Trung Hoà-Nhân Chính, Phường Nhân Chính, Quận Thanh Xuân, Hà Nội",
                'ReceiverName' => "Nguyễn Văn A",
                'Note' => "Test eHoaDon",
                'BillCode' => "",
                'CurrencyID' => "VND",
                'ExchangeRate' => 1,
                'InvoiceStatusID' => 1,
                'SignedDate' => date('m/d/Y h:i:s a', time()),
            ),
            'ListInvoiceDetailsWS' => array(),
            'PartnerInvoiceID' => 0,
            'PartnerInvoiceStringID' => '',
        );

        $items = array(1,2);
        foreach ($items as $item) {
            $CommandObject[0]['ListInvoiceDetailsWS'][] = array(
                'ItemName' => "Chữ ký số Bkav CA ENT BN (bao gồm Thiết bị USB Token) update",
                'UnitName' => "Gói",
                'Qty' => 1,
                'Price' => 600000,
                'Amount' => 600000,
                'TaxRateID' => 3,
                'TaxAmount' => 60000,
                'IsDiscount' => false,
                'IsIncrease' => null,
            );
        }

        $CommandData = array(
            'CommandType' => $CmdType,
            'CommandObject' => $CommandObject
        );

        $CommandData = json_encode($CommandData);
        //$CommandData = unpack("C*",$CommandData); // Convert to byte array
        $token = explode(':', $BkavPartnerToken);

        $result = $this->Encryption($this->Zip($CommandData), $token[0], $token[1], "AES-256-CBC");

        return $result;
   }

   public function Encryption($plaintext, $key, $iv, $method){
        // $ivsize = openssl_cipher_iv_length($method);
        // $iv = openssl_random_pseudo_bytes($ivsize);
        return base64_encode(openssl_encrypt($plaintext, $method, base64_decode($key), OPENSSL_RAW_DATA, base64_decode($iv)));
   }
   public function Decryption($plaintext, $key, $method){
        $ivsize = openssl_cipher_iv_length($method);
        $iv = openssl_random_pseudo_bytes($ivsize);
        return openssl_decrypt(base64_decode($plaintext), $method, base64_decode($key), OPENSSL_RAW_DATA, $iv);
   }
   public function Zip($str){
        return gzencode($str);
   }
   public function Unzip($str){
        return gzuncompress($str);
   }

   public function de(){
    var_dump(hex2bin('8VfXSP8h2GYZsOujKAxxXOjrwrplclW8U+GglMrw6mU='));
    // $cipher = new AES(); 
    // $cipher->setKey('8VfXSP8h2GYZsOujKAxxXOjrwrplclW8U+GglMrw6mU=');
    // $cipher->setIV('slmm5fyrdZDQASy7hjQ33g==');
    // echo $encrypted = base64_encode($cipher->encrypt('{\"CmdType\":100,\"CommandObject\":[{\"Invoice\":{\"InvoiceTypeID\":1,\"InvoiceDate\":\"05/04/2018 10:14:59 am\",\"BuyerName\":\"Nguy\u1ec5n V\u0103n A Update\",\"BuyerTaxCode\":\"0104746603\",\"BuyerUnitName\":\"C\u00f4ng Ty Lu\u1eadt TNHH ABC\",\"BuyerAddress\":\"Nh\u00e0 N2D Khu \u0110T Trung Ho\u00e0-Nh\u00e2n Ch\u00ednh, Ph\u01b0\u1eddng Nh\u00e2n Ch\u00ednh, Qu\u1eadn Thanh Xu\u00e2n, H\u00e0 N\u1ed9i\",\"BuyerBankAccount\":\"\",\"PayMethodID\":1,\"ReceiveTypeID\":3,\"ReceiverEmail\":\"ngoton007@yahoo.com\",\"ReceiverMobile\":\"0902085911\",\"ReceiverAddress\":\"Nh\u00e0 N2D Khu \u0110T Trung Ho\u00e0-Nh\u00e2n Ch\u00ednh, Ph\u01b0\u1eddng Nh\u00e2n Ch\u00ednh, Qu\u1eadn Thanh Xu\u00e2n, H\u00e0 N\u1ed9i\",\"ReceiverName\":\"Nguy\u1ec5n V\u0103n A\",\"Note\":\"Test eHoaDon\",\"BillCode\":\"\",\"CurrencyID\":\"VND\",\"ExchangeRate\":1,\"InvoiceStatusID\":1,\"SignedDate\":\"05/04/2018 10:14:59 am\"},\"ListInvoiceDetailsWS\":[{\"ItemName\":\"Ch\u1eef k\u00fd s\u1ed1 Bkav CA ENT BN (bao g\u1ed3m Thi\u1ebft b\u1ecb USB Token) update\",\"UnitName\":\"G\u00f3i\",\"Qty\":1,\"Price\":600000,\"Amount\":600000,\"TaxRateID\":3,\"TaxAmount\":60000,\"IsDiscount\":false,\"IsIncrease\":null},{\"ItemName\":\"Ch\u1eef k\u00fd s\u1ed1 Bkav CA ENT BN (bao g\u1ed3m Thi\u1ebft b\u1ecb USB Token) update\",\"UnitName\":\"G\u00f3i\",\"Qty\":1,\"Price\":600000,\"Amount\":600000,\"TaxRateID\":3,\"TaxAmount\":60000,\"IsDiscount\":false,\"IsIncrease\":null}],\"PartnerInvoiceID\":0,\"PartnerInvoiceStringID\":\"\"}]}'));

    // var_dump($cipher->decrypt(base64_decode("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")));

    $a = $this->Zip('{\"CmdType\":100,\"CommandObject\":[{\"Invoice\":{\"InvoiceTypeID\":1,\"InvoiceDate\":\"05/04/2018 10:14:59 am\",\"BuyerName\":\"Nguy\u1ec5n V\u0103n A Update\",\"BuyerTaxCode\":\"0104746603\",\"BuyerUnitName\":\"C\u00f4ng Ty Lu\u1eadt TNHH ABC\",\"BuyerAddress\":\"Nh\u00e0 N2D Khu \u0110T Trung Ho\u00e0-Nh\u00e2n Ch\u00ednh, Ph\u01b0\u1eddng Nh\u00e2n Ch\u00ednh, Qu\u1eadn Thanh Xu\u00e2n, H\u00e0 N\u1ed9i\",\"BuyerBankAccount\":\"\",\"PayMethodID\":1,\"ReceiveTypeID\":3,\"ReceiverEmail\":\"ngoton007@yahoo.com\",\"ReceiverMobile\":\"0902085911\",\"ReceiverAddress\":\"Nh\u00e0 N2D Khu \u0110T Trung Ho\u00e0-Nh\u00e2n Ch\u00ednh, Ph\u01b0\u1eddng Nh\u00e2n Ch\u00ednh, Qu\u1eadn Thanh Xu\u00e2n, H\u00e0 N\u1ed9i\",\"ReceiverName\":\"Nguy\u1ec5n V\u0103n A\",\"Note\":\"Test eHoaDon\",\"BillCode\":\"\",\"CurrencyID\":\"VND\",\"ExchangeRate\":1,\"InvoiceStatusID\":1,\"SignedDate\":\"05/04/2018 10:14:59 am\"},\"ListInvoiceDetailsWS\":[{\"ItemName\":\"Ch\u1eef k\u00fd s\u1ed1 Bkav CA ENT BN (bao g\u1ed3m Thi\u1ebft b\u1ecb USB Token) update\",\"UnitName\":\"G\u00f3i\",\"Qty\":1,\"Price\":600000,\"Amount\":600000,\"TaxRateID\":3,\"TaxAmount\":60000,\"IsDiscount\":false,\"IsIncrease\":null},{\"ItemName\":\"Ch\u1eef k\u00fd s\u1ed1 Bkav CA ENT BN (bao g\u1ed3m Thi\u1ebft b\u1ecb USB Token) update\",\"UnitName\":\"G\u00f3i\",\"Qty\":1,\"Price\":600000,\"Amount\":600000,\"TaxRateID\":3,\"TaxAmount\":60000,\"IsDiscount\":false,\"IsIncrease\":null}],\"PartnerInvoiceID\":0,\"PartnerInvoiceStringID\":\"\"}]}');
    var_dump(unpack("C*",utf8_encode('{\"CmdType\":100,\"CommandObject\":[{\"Invoice\":{\"InvoiceTypeID\":1,\"InvoiceDate\":\"05/04/2018 10:14:59 am\",\"BuyerName\":\"Nguy\u1ec5n V\u0103n A Update\",\"BuyerTaxCode\":\"0104746603\",\"BuyerUnitName\":\"C\u00f4ng Ty Lu\u1eadt TNHH ABC\",\"BuyerAddress\":\"Nh\u00e0 N2D Khu \u0110T Trung Ho\u00e0-Nh\u00e2n Ch\u00ednh, Ph\u01b0\u1eddng Nh\u00e2n Ch\u00ednh, Qu\u1eadn Thanh Xu\u00e2n, H\u00e0 N\u1ed9i\",\"BuyerBankAccount\":\"\",\"PayMethodID\":1,\"ReceiveTypeID\":3,\"ReceiverEmail\":\"ngoton007@yahoo.com\",\"ReceiverMobile\":\"0902085911\",\"ReceiverAddress\":\"Nh\u00e0 N2D Khu \u0110T Trung Ho\u00e0-Nh\u00e2n Ch\u00ednh, Ph\u01b0\u1eddng Nh\u00e2n Ch\u00ednh, Qu\u1eadn Thanh Xu\u00e2n, H\u00e0 N\u1ed9i\",\"ReceiverName\":\"Nguy\u1ec5n V\u0103n A\",\"Note\":\"Test eHoaDon\",\"BillCode\":\"\",\"CurrencyID\":\"VND\",\"ExchangeRate\":1,\"InvoiceStatusID\":1,\"SignedDate\":\"05/04/2018 10:14:59 am\"},\"ListInvoiceDetailsWS\":[{\"ItemName\":\"Ch\u1eef k\u00fd s\u1ed1 Bkav CA ENT BN (bao g\u1ed3m Thi\u1ebft b\u1ecb USB Token) update\",\"UnitName\":\"G\u00f3i\",\"Qty\":1,\"Price\":600000,\"Amount\":600000,\"TaxRateID\":3,\"TaxAmount\":60000,\"IsDiscount\":false,\"IsIncrease\":null},{\"ItemName\":\"Ch\u1eef k\u00fd s\u1ed1 Bkav CA ENT BN (bao g\u1ed3m Thi\u1ebft b\u1ecb USB Token) update\",\"UnitName\":\"G\u00f3i\",\"Qty\":1,\"Price\":600000,\"Amount\":600000,\"TaxRateID\":3,\"TaxAmount\":60000,\"IsDiscount\":false,\"IsIncrease\":null}],\"PartnerInvoiceID\":0,\"PartnerInvoiceStringID\":\"\"}]}')));
    //var_dump($this->Decryption("vi/IdLJI1+yqz+9vXc3DYi+y/4VJEUrzvMkLswqHfSJvgfdcVQOKwPTqq6THdU82DW51x1X/5pUNzwlBiC+WDDN5L0xDQ2evzfsO+ZrNTTWz9wPftyeckcaZseF5ftqq6ZJSQjmWZVg1HEJ42TVKyoMpd6XOZqacEWZXwgDq2X91zlAPf5ZV5Ab4C67iCPQK8ibpFa61zoLQP6b//GHIfRUN/3IYXum+vVe3IJIJPRNv2oU+UYmPwONRZ5NEQU8sFPCJpVz8yNCyojPLFh3F/4MtcJnfbQmBsu8ntPTZFnLN/wKJ66Zgmqnm5YrVGWIhy+sZd748zrHBFLRBE4VykFxGBrg0IrGfPo5WOwlQD0HdiayF2wtS4ax7h9xwmI4SevTo7quhwlN3BRS4rfI1SSvX/q2EuOFSntoA9uETBWcKTe6usvOvl+qe+oaC3WzQNae6mpl+6x+LKTBBEV5MW2y1lmd/JfYPoDfPmJTJjSkN00/1s39I0zQIec2uMvu2eb1rq8wbkw6PbNYxUiPv92OTftcDiSmowP7BUlK7jP4Zn5gK4/4xDJB/yix2Xb4IsVcRJB8bhstIvU9D/f6PT09LCYAayA7ZO/tg2jTwVmVUpCTeTKR+P0kaeL3iXS9MaEuDNR5THlKB/uI+lLt62SYKjS5ETJo5hGuoct797WNpAyrN59dR89tKJ0jAbPpc8829pcjVtNfkn5QUtYfQDrzpgvSAwOq0xUGJ+MAkpy/nReOdbi56LrTTpaCeMrEZQvyE81aNZvt46Gs1tQMRTpVyHL5r5DMQcqMqHdp+082eLl2FF6f8Z8tI5U3yPtcHSYDJuaNtnd+xt5MLbR5iXVWpqJlxpct4XQe5bzfdtdf5seFwGn0M730Tqer3CiS+pNmNOgxgpAgYWnlF8l6AqIMpd6XOZqacEWZXwgDq2X+r7eVj2oDkQxUye06+cnk92QxwbJzLRwo13378h4edxUMvS50Rqbizs2DkNnntGbYvBc9q1eJCz4k9H+h7YG2j/4Yriu5gTqe0aKkGJ2XIWZyOjgGQhHylrf+3B5gxKaPkqFfBLLJQY0nFcx92iEqEJVVFozOrv/cHv6qBwPvG/SR1yYFwENKxM1G4ZMdF+aAYnQTp3FRFxGSdV+F3BWcQuJoTMNkSI004nZJ1P+BfJio8fb7Sex4IRiJy4jt6Gv3IGBRBGkR0KdRz2xViy0qx01tduXd7IpIwWRkftxC3rsFhLSxCSc/flqzZpIhT+Rx8P8lrxdpjeZJgVs1Sur+yy2wAxLSAlVGbGaAo26BiGCh03VTh6uPQUVm94kob9A5zgV+oSnzwMF4QPTef9on7vHy5aC/LTM3kVtBQG+Ma2NhsymsJkXAq3YrCXx64fiTRCtT2z1FsSkiSLdwlt2GfGvHGd98dKUnrxhIqyB28DD8KakNHgfowFxskDBT+PEHJ0Z9NZG93k7BdGzMNMk1WNJrdcc+e9CJBbl3zt8iA3jBE5+BmgczNPwYcaGJc+iiWcLlwizx5YXtGSY/AdkfDtHbiEn3zs5fPwgASWRInv9BHa6iYoEGZs5lFczPHc3Y/qwzy3URKbt8vHv2n+dece7M/14ExigadlL3r/SsJA0o0+l1fxPmpcquS7XIJzIZypPzJzaG08koxnKTmFbMz3F0Eux9uiRrrn/zp5TxQf+V1s7bOJmQAcWOsItS5D2aK3BO0EOOhpaHoYEoVwCag92odQHvGYIj5RyfrBI8m+nmdYy455yZGATGRLDl6VBi+niQ67KyB31C+Awxbtd0ngJjRbAu/hkXdCRv6/gMuNDGSRUPuZ0xlZOpzLPzSXOU4kKAA2pRfPQNWvXeOrCdPbas0TkYVFtScCFu1OyZwdA==","8VfXSP8h2GYZsOujKAxxXOjrwrplclW8U+GglMrw6mU=:slmm5fyrdZDQASy7hjQ33g==", "aes-256-ecb" ));
   }
   public function data(){
        $CommandObject[] = array(
            'Invoice' => array(
                'InvoiceTypeID' => 1,
                'InvoiceDate' => date('m/d/Y h:i:s a', time()),
                'BuyerName' => "Nguyễn Văn A Update",
                'BuyerTaxCode' => "0104746603",
                'BuyerUnitName' => "Công Ty Luật TNHH ABC",
                'BuyerAddress' => "Nhà N2D Khu ĐT Trung Hoà-Nhân Chính, Phường Nhân Chính, Quận Thanh Xuân, Hà Nội",
                'BuyerBankAccount' => "",
                'PayMethodID' => 1,
                'ReceiveTypeID' => 3,
                'ReceiverEmail' => "ngoton007@yahoo.com",
                'ReceiverMobile' => "0902085911",
                'ReceiverAddress' => "Nhà N2D Khu ĐT Trung Hoà-Nhân Chính, Phường Nhân Chính, Quận Thanh Xuân, Hà Nội",
                'ReceiverName' => "Nguyễn Văn A",
                'Note' => "Test eHoaDon",
                'BillCode' => "",
                'CurrencyID' => "VND",
                'ExchangeRate' => 1,
                'InvoiceStatusID' => 1,
                'SignedDate' => date('m/d/Y h:i:s a', time()),
            ),
            'ListInvoiceDetailsWS' => array(),
            'PartnerInvoiceID' => 0,
            'PartnerInvoiceStringID' => '',
        );

        $items = array(1,2);
        foreach ($items as $item) {
            $CommandObject[0]['ListInvoiceDetailsWS'][] = array(
                'ItemName' => "Chữ ký số Bkav CA ENT BN (bao gồm Thiết bị USB Token) update",
                'UnitName' => "Gói",
                'Qty' => 1,
                'Price' => 600000,
                'Amount' => 600000,
                'TaxRateID' => 3,
                'TaxAmount' => 60000,
                'IsDiscount' => false,
                'IsIncrease' => null,
            );
        }

        $CommandData = array(
            'CmdType' => 100,
            'CommandObject' => $CommandObject
        );

        $CommandData = json_encode($CommandData);
        $CommandData = unpack('C*', $CommandData);
        var_dump($CommandData);
        //echo $this->Zip($CommandData);
   }

}
?>