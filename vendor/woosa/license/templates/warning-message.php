<?php
/**
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;

?>
<h3><?php printf(__('Your license for "%s" is not active.', 'convesiopay-woocommerce'), '<em>' . NAME . '</em>');?></h3>
<p><?php printf(
   __(
      'This means you will not receive automatic plugin updates or support from our Support Specialists.
      Please activate your Woosa license %shere%s or %sread where to find the license%s.'
      , 'convesiopay-woocommerce'
   ),
   '<a href="'.SETTINGS_URL.'&tab=dashboard">', '</a>',
   '<a href="https://help.woosa.com/en/articles/42106-activating-your-woosa-license-key" target="_blank">', '</a>'
);?></p>