<?php
/**
 * Interface Hook Worker Format Task
 *
 * This interface is dedicated for formatting a task via `Worker` module hook.
 *
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


interface Interface_Hook_Worker_Format_Task {


   /**
    * Initiates the hooks.
    *
    * @return void
    */
   public static function init();



   /**
    * Formats the task before processing.
    *
    * @param array $task
    * @return array
    */
   public static function format_task($task);

}