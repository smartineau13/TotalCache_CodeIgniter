#TotalCache library for CodeIgniter

##How to use library TotalCache for CodeIgniter ?


1- Copy the library in application/libraries

2- To load it in CodeIgniter there are two possibilities:
In application/config/autoload.php look for the following instruction $autoload['libraries']= array(); and add totalcache. 
 
 >
 |$autoload['libraries']= array('totalcache');|      
 |---------------- |
 ||


This way the library is loaded for the whole application.
Load it directly in the class where you want to use it. 

>
|$this->load->library('totalcache');|      
 |---------------- |
 ||


3- In your controller, instead of calling $this->load->view('view_name',$data) to load view, use method viewAndCache of library TotalCache

>
 |$this->totalchache->viewAndCache('view_name', $data);|      
 |---------------- |
 ||

>

Example:
>
 | yourwebsite.com/controller/method/param|      
 |---------------- |
 ||


viewAndCache() method will create the file param.html which will contain the view generated by CodeIgniter. This file is located in the following directory tree created (if it doesn't exist) in the method:  yourwebsite.com/static/controller/method 

4- So that the cache file is served directly without invoking CodeIgniter, you need to add these instructions in your htaccess file at your website's root.

>_RewriteEngine on_  
<<<<<<< HEAD
>_RewriteCond %{DOCUMENT_ROOT}/yourwebsite.com/static/$1 -f_  
>_RewriteRule ^(.*)$ static/$1 [L]_  
>RewriteCond $1 !^(static|index\.php|images|robots\.txt)_  
>RewriteRule ^(.*)$ index.php/$1 [L]_
=======
>__RewriteCond %{DOCUMENT_ROOT}/yourwebsite.com/static/$1 -f__  
>__RewriteRule ^(.*)$ static/$1 [L]__  
>__RewriteCond $1 !^(static|index\.php|images|robots\.txt)__ 
>__RewriteRule ^(.*)$ index.php/$1 [L]__
>>>>>>> origin/master



#Htaccess editor for total cache

##How to use Htaccess editor


If you're not very confortable with htaccess instructions you can use the htaccess editor.  
1- Upload it at your website's root.   
2- Run it by entering this url in your browser: yourwebsite.com/htaccess_editor   
3- Enter a password so that nobody else can access the editor  
4- Enter your own parameters  
5- Delete tool from server when you don't need it anymore.  

##Functions  
  
  
__Change site path__

If you changed the name of your website or put your content in a sub-directory, you may need to change site path.


__Change cache path__

The default directory to save cache files is "static". You can modify it.




__Fully disable cache__

Total cache is disabled for the whole application, it avoids you modifying your code

__Partially disable cache__

You can choose for which controller or method you want to disable cache without modifying your code.

__Enable cache__

Fully enables cache.
