<?php
/**
 * Module Abstract Product Task Marketplace
 *
 * This is dedicated for processing a shop product to the marketplace entity.
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


/**
 * @property string $id
 * @property string $offer_id
 * @property string $account_id
 * @property array $account
 * @property string $type
 * @property Module_Meta $meta
 * @property string $ean
 * @property string $reference
 * @property string $on_hold
 * @property string $price
 * @property int|null $stock
 * @property string $condition
 * @property string $force_pause
 */
abstract class Module_Abstract_Product_Task_Marketplace{


   /**
    * Throw exception code for general
    */
   const GENERAL_ERROR_CODE = 10;


   /**
    * Throw exception code for account
    */
   const ACCOUNT_ERROR_CODE = 20;


   /**
    * The product id in shop.
    *
    * @var string
    */
   protected string $id;


   /**
    * The offer (e.g. product, unit, etc) id in markeplace.
    *
    * @var string
    */
   protected string $offer_id;


   /**
    * The Marketplace account id.
    *
    * @var string
    */
   protected string $account_id;


   /**
    * The Marketplace account.
    *
    * @var array
    */
   protected array $account;


   /**
    * The product type in shop.
    *
    * @var string
    */
   protected string $type;


   /**
    * @var Module_Meta
    */
   protected Module_Meta $meta;


   /**
    * The product ean in shop.
    *
    * @var string
    */
   protected string $ean;


   /**
    * The product reference.
    *
    * @var string
    */
   protected string $reference;


   /**
    * Whether or not the product is on-hold.
    *
    * @var string
    */
   protected string $on_hold;


   /**
    * The product price.
    *
    * @var string
    */
   protected string $price;


   /**
    * The product stock.
    *
    * @var int|null
    */
   protected ?int $stock;


   /**
    * The product condition.
    *
    * @var string
    */
   protected string $condition;


   /**
    * Whether or not to force pausing the offer.
    *
    * @var string
    */
   protected string $force_pause;



   /**
    * Construct of this class
    *
    * @param array $account
    * @param array $data
    * @throws \Exception
    */
   public function __construct(array $account, array $data) {

      $this->id         = Util::array($data)->get('id');
      $this->account_id = Util::array($account)->get('account_id');
      $this->account    = $account;

      $this->meta = new Module_Meta($this->id);
      $this->meta->set_account_id($this->account_id);

      $this->type        = Util::array($data)->get('type');
      $this->stock       = Util::array($data)->get('meta_data/_stock');
      $this->ean         = Util::array($data)->get('meta_data/' . Util::prefix('ean'));
      $this->reference   = Util::array($data)->get('meta_data/' . Util::prefix('reference'));
      $this->condition   = Util::array($data)->get('meta_data/' . Util::prefix('condition'));
      $this->offer_id    = Util::array($data)->get('meta_data/' . Util::prefix($this->account_id . '_product_id'));
      $this->on_hold     = Util::array($data)->get('meta_data/' . Util::prefix($this->account_id . '_on_hold'));
      $this->price       = Util::array($data)->get('meta_data/' . Util::prefix($this->account_id . '_price'));
      $this->force_pause = Util::array($data)->get('custom_data/force_pause', 'no');
   }


   /**
    * List of supported actions.

    * @return array
    */
   public static function action_list() {

      return [
         'create_or_update_product',
         'pause_or_unpause_product',
         'delete_or_trash_product'
      ];
   }



   /**
    * Checks whether or not the shop product type is supported.
    *
    * @return boolean
    */
   public function is_supported_type(){

      $list = ['simple', 'variation'];

      return in_array($this->type, $list);
   }



   /**
    * Checks whether or not the shop product is valid.
    *
    * @return boolean
    */
   public function is_valid(){
      return $this->is_supported_type() && wc_get_product($this->id) instanceof \WC_Product;
   }



   /**
    * Checks whether or not the given shop product EAN/GTN belongs to an existing product in the marketplace.
    *
    * @return boolean
    */
   abstract public function is_product_in_catalog();



   /**
    * Checks whether or not the offer is paused.
    *
    * @return bool
    */
   public function is_on_hold(){
      return Util::string_to_bool($this->on_hold);
   }



   /**
    * Checks whether or not the offer pause is forced.
    *
    * @return bool
    */
   public function is_pause_forced(){
      return Util::string_to_bool($this->force_pause);
   }



   /**
    * Retrieves the shop product id.
    *
    * @return string|int
    */
   public function get_id() {
      return $this->id;
   }



   /**
    * Retrieves the marketplace product id.
    *
    * @return string|int
    */
   public function get_remote_id() {
      return $this->offer_id;
   }



   /**
    * Retrieves the shop product type.
    *
    * @return string
    */
   public function get_type() {
      return $this->type;
   }



   /**
    * Retrieves the price to be sent to the marketplace.
    *
    * @return string
    */
   abstract public function get_price();



   /**
    * Retrieves the stock to be sent to the marketplace.
    *
    * @return int
    */
   public function get_stock(){

      if($this->stock > 0){
         $this->stock = (int) $this->stock - (int) Option::get('preserve_stock_offset', 0);
      }

      return (int) $this->stock;
   }



   /**
    * Retrieves the status to be sent to the marketplace.
    *
    * @return string
    */
   abstract public function get_status();



   /**
    * Handles the exception errors.
    *
    * @param \Exception $e
    * @return void
    */
   public function handle_errors(\Exception $e){

      //set status error always per account
      $this->meta->set_account_id($this->account_id);
      $this->meta->set_status('error');

      //remove account id to set the errors as general
      if(self::GENERAL_ERROR_CODE == $e->getCode()){
         $this->meta->remove_account_id();
      }

      //set account id to set the errors per account
      if(self::ACCOUNT_ERROR_CODE == $e->getCode()){
         $this->meta->set_account_id($this->account_id);
      }

      $errors = Util::is_json($e->getMessage()) ? json_decode($e->getMessage()) : $e->getMessage();

      if(is_array($errors)){

         foreach($errors as $error){
            $this->meta->set_error($error);
         }

      }else{
         $this->meta->set_error($errors);
      }

      $this->meta->save();
   }



   /**
    * Creates the marketplace product.
    *
    * @return void
    */
   abstract public function create();



   /**
    * Updates the marketplace product.
    *
    * @return void
    */
   abstract public function update();



   /**
    * Deletes the marketplace product.
    *
    * @return void
    */
   abstract public function delete();

}