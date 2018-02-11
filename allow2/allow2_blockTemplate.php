<?php
 $language = get_bloginfo('language');
 $charset = get_bloginfo('charset');
 $name = get_bloginfo('name');
 $url = get_bloginfo('url');
// $link_text = sprintf(wp_kses(__('<a title="%s" href="%s">%s</a> is currently undergoing scheduled maintenance.', 'simple-maintenance'), array('a' => array('href' => array(), 'title' => array()))), $name, esc_url($url), $name);
?>
<!DOCTYPE html>
<html lang="<?php echo $language; ?>">
<head>
    <meta charset="<?php echo $charset; ?>" />
    <meta name="viewport" content="width=device-width">
    <title><?php echo $name; ?> &#8250; Allow2 Limited</title>
    <link rel="profile" href="http://gmpg.org/xfn/11">
    <link rel="stylesheet" href="<?php echo SIMPLE_MAINTENANCE_URL.'/sm-style.css' ?>" type="text/css" media="all" />	
</head>
<body>
    <div id="header">
        <h2><a title="<?php echo $name; ?>" href="<?php echo $url; ?>"><?php echo $name; ?></a></h2>
    </div>	
    <div id="content">
        <h1><?php _e('Maintenance Mode', 'simple-maintenance')?></h1>
        <p><?php echo $link_text;?></p>
        <p><?php _e('Please try back again soon.', 'simple-maintenance')?></p>
        <p><?php _e('Sorry for the inconvenience.', 'simple-maintenance')?></p>			
    </div>
</body>
</html>