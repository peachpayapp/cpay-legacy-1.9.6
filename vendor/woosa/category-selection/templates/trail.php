<?php
/**
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


$index = 0;
$total = count($items);
?>

<?php foreach($items as $id => $name):
   if(0 == $index):?>
      <a href="#" data-<?php echo PREFIX;?>-cs-load-items="0"><?php echo $name;?></a>
   <?php elseif(($total - 1) === $index):?>
      <span>»</span>&nbsp;<span><?php echo $name;?></span>
   <?php else:?>
      <span>»</span>&nbsp;<a href="#" data-<?php echo PREFIX;?>-cs-load-items="<?php echo $id;?>"><?php echo $name;?></a>
   <?php endif;?>
<?php $index++; endforeach;?>