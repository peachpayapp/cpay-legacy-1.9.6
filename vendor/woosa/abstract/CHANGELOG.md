## 1.1.0 - 2024-10-15

### Added

* New abstract for processing product task for marketplace

## 1.0.1 - 2023-07-27

### Changed

* Remove unnecessary parameter `$this->data` from the hooks, the instance of the class should be enough
* On update post it checks if the array keys exist and only then it will define the columns
* Adjust method `Module_Abstract_Entity_Post::get_id_from_data()` to use only the metadata which is not empty

### Fixed

* The method `Module_Abstract_Entity_Post::trash()` is deleteing the post completely instead to move it to trash
* The SQL statement `INSERT IGNORE INTO...` for creating metadata can cause the error `Deadlock found when trying to get lock; try restarting transaction` therefor let's use `add_post_meta()` instead