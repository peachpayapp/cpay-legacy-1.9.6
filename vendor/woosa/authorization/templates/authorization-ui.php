<?php
/**
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;

/**
 * @var Module_Authorization $authorization
 */

?>

<tr class="<?php echo PREFIX;?>-style">
   <td class="p-0">
      <div>
         <span class="tb"><?php _e('Status ', 'convesiopay-woocommerce');?></span>
         <?php echo $status; ?>
      </div>

      <?php if (!empty($authorization->get_wiki_article_url())): ?>
         <p class="pt-20"><?php
            printf(
               __('Questions about the authorization of your ConvesioPay account? Read our %sHelp Center article%s, we will guide you step-by-step through the process.', 'convesiopay-woocommerce'),
               sprintf(
                  '<a href="%s" target="_blank" class="tb">',
                  $authorization->get_wiki_article_url()
               ),
               '</a>'
            );
         ?></p>
      <?php endif; ?>

      <?php do_action(PREFIX . '\authorization\output_section\fields', $authorization);?>

      <div class="pt-15">
         <button type="button" class="button <?php echo $authorization->is_authorized() ? 'button-secondary' : 'button-primary';?>" <?php echo $button['data-attr'];?>><?php echo $button['label'];?></button>
      </div>
   </td>
</tr>