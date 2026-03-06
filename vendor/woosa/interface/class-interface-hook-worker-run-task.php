<?php
/**
 * Interface Hook Worker Run Task
 *
 * This interface is dedicated for processing a task via `Worker` module hook.
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


interface Interface_Hook_Worker_Run_Task {


   /**
    * Initiates the hooks.
    *
    * @return void
    */
   public static function init();



   /**
    * Processes the task.
    *
    * @param bool|array $processed
    * @param array $task
    * @return bool
    */
   public static function process_task($processed, array $task);



   /**
    * Processes the entity based on the given action.
    *
    * @param object $entity
    * @param string $action
    * @return void
    */
   public static function process_action($entity, string $action);

}