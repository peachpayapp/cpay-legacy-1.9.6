<?php
/**
 * Util Alertbox
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Util_Alertbox{


   /**
    * The content of the notification
    *
    * @var string
    */
   protected $content = '';


   /**
    * The title of the notification
    *
    * @var string
    */
   protected $title = '';



   /**
    * Initiates the instance and sets the content.
    *
    * @param string $content
    * @return Module_Notification
    */
   public function content(string $content){

      $this->content = $content;

      return $this;
   }



   /**
    * Sets the title.
    *
    * @param string $content
    * @return Module_Notification
    */
   public function title(string $title){

      $this->title = $title;

      return $this;
   }



   /**
    * Renders the notification output.
    *
    * @param string $type
    * @param bool $render
    * @return string
    */
   protected function render(string $type = 'blue', bool $render = true){

      ob_start();
      ?>
      <div class="mb-20 alertbox alertbox--<?php echo $type;?>">
         <?php if(!empty($this->title)):?>
            <h3><?php echo $this->title;?></h3>
         <?php endif;?>
         <?php echo $this->content;?>
      </div>
      <?php
      $output = ob_get_clean();

      if($render){
         echo $output;
      }

      return $output;
   }



   /**
    * Renders the success notification.
    *
    * @return string
    */
   public function success($render = true){
      $this->render('green', $render);
   }



   /**
    * Renders the info notification.
    *
    * @return string
    */
   public function info($render = true){
      $this->render('blue', $render);
   }



   /**
    * Renders the warning notification.
    *
    * @return string
    */
   public function warning($render = true){
      $this->render('yellow', $render);
   }



   /**
    * Renders the error notification.
    *
    * @return string
    */
   public function error($render = true){
      $this->render('red', $render);
   }
}