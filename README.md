
# Util Functions
### vnbiz_get_var(&$var, $default = null)
### vnbiz_get_key(&$var, $key, $default = null)
### vnbiz_str_starts_with($string, $startString)
### vnbiz_assure_valid_name($name)


# Handler
### vnbiz_handle_restful()
	for /api


# VnBiz Functions

### vnbiz()
	Get Vnbiz Instance
	
### vnbiz_model($model_name)
	Get Model Definition Object
	
### vnbiz_model_add($model_name)
	Create new Model
	
### vnbiz_get_model_field_names($model_name)
	Get array of field name

### vnbiz_assure_model_name_exists($model_name)
	Will throw VnBizError if the model doesn't exist

### vnbiz_do_action($name, &$context)
	Execute an action

### vnbiz_add_action($name, $func)
	Register an action

### vnbiz_do_service($service_name, $params = [])
	Execute a service (action "service_*)

### vnbiz_model_delete($model_name, $filter)
	delete model

### vnbiz_sql_generate()
	generate and return sql script

### vnbiz_sql_alter_tables()
	generate & execute sql script

# Authentication

### vnbiz_user()
	Get Current Logged in User.

### vnbiz_user_has_permissions(..)
	Return TRUE if the current user has one of the permissions on the params.	


# Model Functions

### vnbiz_model_create($model_name, $model)
	Create an model object & persist to database

### vnbiz_model_search(&$context)
	Search from database

### vnbiz_model_count($model_name, $filter = [])
	Count rows by filter

### vnbiz_model_find($model_name, $filter = [], $meta = ['limit' => 10, 'offset' => 0])
	Find or search

### vnbiz_model_find_one($model_name, $filter = [], $meta = [])
	find first row

### vnbiz_model_update($model_name, $filter, $model, $meta = [])
	Update a model data

# Example Search

```
POST /api

action = model_find
model_name	= user
filter[status] = active
meta[count] = true
meta[text_search] = 'gmail'
meta[ref] = true
```

# To Use

```
vnbiz()->init_db_mysql('localhost', 'vnrootco_dev', 'eIVtmk3q_8H7', 'vnrootco_prodesk');
vnbiz_model_add('project')
	->...;

vnbiz()->start();

```