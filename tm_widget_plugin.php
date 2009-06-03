<?php
/*
Plugin Name: thirstymind.org widgets
Plugin URI: thirstymind.org
Description: Widgets used on thirstymind.org
Version: 0.0.1
Author: Andrew Watts
Author URI: http://www.thirstymind.org
*/


    require_once( ABSPATH . WPINC . '/rss.php');  
    
/*     if ( !function_exists('fetch_rss') ) : */



    /**
     * Build Magpie object based on RSS from URL. We are using a custom
     * function to handle the cache age the way we want, ie: the cache
     * age can be different per widget.
     *
     * @since unknown
     * @package External
     * @subpackage MagpieRSS
     *
     * @param string $url URL to retrieve feed
     * @return bool|MagpieRSS false on failure or MagpieRSS object on success.
     */
     
    function fetch_rss ($url, $cache_age) {
    	// initialize constants
    	init();
    
    	if ( !isset($url) ) {
    		// error("fetch_rss called without a url");
    		return false;
    	}
    
    	// if cache is disabled
    	if ( !MAGPIE_CACHE_ON ) {
    		// fetch file, and parse it
    		$resp = _fetch_remote_file( $url );
    		if ( is_success( $resp->status ) ) {
    			return _response_to_rss( $resp );
    		}
    		else {
    			// error("Failed to fetch $url and cache is off");
    			return false;
    		}
    	}
    	// else cache is ON
    	else {
    		// Flow
    		// 1. check cache
    		// 2. if there is a hit, make sure its fresh
    		// 3. if cached obj fails freshness check, fetch remote
    		// 4. if remote fails, return stale object, or error
    
            $cache_age = isset($cache_age) ? $cache_age : MAGPIE_CACHE_AGE;
    
    		$cache = new RSSCache( MAGPIE_CACHE_DIR, $cache_age);
    
    		if (MAGPIE_DEBUG and $cache->ERROR) {
    			debug($cache->ERROR, E_USER_WARNING);
    		}
    
    		$cache_status 	 = 0;		// response of check_cache
    		$request_headers = array(); // HTTP headers to send with fetch
    		$rss 			 = 0;		// parsed RSS object
    		$errormsg		 = 0;		// errors, if any
    
    		if (!$cache->ERROR) {
    			// return cache HIT, MISS, or STALE
    			$cache_status = $cache->check_cache( $url );
    		}
    
    		// if object cached, and cache is fresh, return cached obj
    		if ( $cache_status == 'HIT' ) {
    			$rss = $cache->get( $url );
    			
    			if ( isset($rss) and $rss ) {
    			
                    $rss->from_cache = 1;
    				if ( MAGPIE_DEBUG > 1) {
    				    debug("MagpieRSS: Cache HIT", E_USER_NOTICE);
                    }
                    
    				return $rss;
    			}
    		}

    		// else attempt a conditional get
    
    		// setup headers
    		if ( $cache_status == 'STALE' ) {
    			$rss = $cache->get( $url );
    			if ( $rss->etag and $rss->last_modified ) {
    				$request_headers['If-None-Match'] = $rss->etag;
    				$request_headers['If-Last-Modified'] = $rss->last_modified;
    			}
    		}
    		

    
    		$resp = _fetch_remote_file( $url, $request_headers );
    
    		if (isset($resp) and $resp) {
    			if ($resp->status == '304' ) {
    				// we have the most current copy
    				if ( MAGPIE_DEBUG > 1) {
    					debug("Got 304 for $url");
    				}
    				// reset cache on 304 (at minutillo insistent prodding)
    				$cache->set($url, $rss);
    				return $rss;
    			}
    			elseif ( is_success( $resp->status ) ) {
    				$rss = _response_to_rss( $resp );
    				if ( $rss ) {
    					if (MAGPIE_DEBUG > 1) {
    						debug("Fetch successful");
    					}
    					// add object to cache
    					$cache->set( $url, $rss );
    					return $rss;
    				}
    			}
    			else {
    				$errormsg = "Failed to fetch $url. ";
    				if ( $resp->error ) {
    					# compensate for Snoopy's annoying habbit to tacking
    					# on '\n'
    					$http_error = substr($resp->error, 0, -2);
    					$errormsg .= "(HTTP Error: $http_error)";
    				}
    				else {
    					$errormsg .=  "(HTTP Response: " . $resp->response_code .')';
    				}
    			}
    		}
    		else {
    			$errormsg = "Unable to retrieve RSS file for unknown reasons.";
    		}
    
    		// else fetch failed
    
    		// attempt to return cached object
    		if ($rss) {
    			if ( MAGPIE_DEBUG ) {
    				debug("Returning STALE object for $url");
    			}
    			return $rss;
    		}
    
    		// else we totally failed
    		// error( $errormsg );
    
    		return false;
    
    	} // end if ( !MAGPIE_CACHE_ON ) {
    } // end fetch_rss()
/*     endif; */





    /**
     * TODO: make this method dynamic and user_description
     */ 
    function tm_widget_about($args) {
	    extract($args);
?>
		<?php echo $before_widget; ?>
    		<h3 class="widgettitle">
                About
            </h3>
            
            <p><img src="<?php echo wp_get_attachment_url(493) ?>" alt="Andrew Watts" title="Andrew Watts" align="left">thirstymind.org is 
            the personal site of Andrew Watts, where I share my thoughts on software, technology &amp; life.</p>  
            
            <ul>
                <li><a href="about" alt="About Andrew Watts" title="About Andrew Watts">More &raquo;</a></li>
                <li><a href="projects" alt="Andrew Watts' Projects" title="Andrew Watts' Projects">Projects &raquo;</a></li>                                
            </ul>
		<?php echo $after_widget; ?>
<?php
    }




    function tm_widget_search($args) {
	    extract($args);
	    
	    $search_value = "Enter Search Term and Hit Enter...";
?>
		<?php echo $before_widget; ?>
    		<h3 class="widgettitle">
                <label for="s">Search</label>
            </h3>
			<form id="searchform" method="get" action="<?php bloginfo('home'); ?>">
			<div>
			<input type="text" name="s" id="s" class="text" size="15" value="<?php echo isset($_GET['s']) ? $_GET['s'] : $search_value ?>" onblur="if (this.value == '') {this.value = '<?php echo $search_value ?>';}" onfocus="if (this.value == '<?php echo $search_value ?>') {this.value = '';}"/>
			<?php /* <input type="submit" value="<?php echo attribute_escape(__('Search')); ?>" /> */ ?>
			</div>
			</form>
		<?php echo $after_widget; ?>
<?php
    }




    function tm_widget_delicious( $args ){
        
        extract( $args );
        
        $options  = get_option( 'tm_widget_delicious' );
        
        $title    = $options['title'];
        $username = $options['username'];
        $count    = $options['count'];
        
        $siteuri  = 'http://del.icio.us/' . $username . '/';
        $feeduri  = 'http://del.icio.us/rss/' . $username . '/';
        
        $feedcontent = @fetch_rss($feeduri);
        
        
        $feeditems   = $feedcontent->items;
        
        $output = '';

        
        for($iter = 0; $iter < $count && $iter < sizeof($feeditems); $iter++){
        
            $linkUri = $feeditems[$iter]['link'];
            $linkTitle = $feeditems[$iter]['title'];

            $output .= '<li>&raquo; <a href="' . $linkUri . '" title="' . $linkTitle . '">' . $linkTitle . '</a></li>' . "\n";
        }


        echo $before_widget . $before_title . '<a href="' . $siteuri . '" title="' . $title . '">' .$title . '</a><!--<a href="' . $feeduri . '"><img src="' . get_bloginfo('stylesheet_directory') . '/images/02.png"></a>-->' . $after_title;
        echo '<ul>' . $output . '</ul>';
        echo $after_widget;
        
    }

    function tm_widget_delicious_control(){
    
        $options = $newoptions = get_option( 'tm_widget_delicious' );
        
        if ( $_POST['tm_widget_delicious-submit'] ) {
        
			$newoptions['title']    = strip_tags(stripslashes($_POST['tm_widget_delicious-title']));
			$newoptions['username'] = strip_tags(stripslashes($_POST['tm_widget_delicious-username']));
			$newoptions['count']    = intval(strip_tags(stripslashes($_POST['tm_widget_delicious-count'])));
			
            if ( $options != $newoptions ) {
                $options = $newoptions;
		        update_option('tm_widget_delicious', $options);
            }
		}
		
		$title    = htmlspecialchars($options['title'], ENT_QUOTES);
		$username = htmlspecialchars($options['username'], ENT_QUOTES);
		$count    = htmlspecialchars($options['count'], ENT_QUOTES);
		
?>		
        <p><label for="tm_widget_delicious-title">Title: <input class="widefat" id="tm_widget_delicious-title" name="tm_widget_delicious-title" type="text" value="<?php echo $title; ?>" /></label></p>
        <p><label for="tm_widget_delicious-username">Username: <input class="widefat" id="tm_widget_delicious-username" name="tm_widget_delicious-username" type="text" value="<?php echo $username; ?>" /></label></p>
        <p><label for="tm_widget_delicious-count">Link count: <input class="widefat" id="tm_widget_delicious-count" name="tm_widget_delicious-count" type="text" value="<?php echo $count; ?>" /></label></p>
        <input type="hidden" id="tm_widget_delicious-submit" name="tm_widget_delicious-submit" value="1" />
<?php    
    }
    
    
    
    
    function tm_widget_flickr( $args ){
        
        extract( $args );
        
        $options  = get_option( 'tm_widget_flickr' );
        
        $title   = $options['title'];
        $feeduri = $options['feeduri'];
        $count   = $options['count'];
        
        $feedcontent = @fetch_rss($feeduri);
        $siteuri     = $feedcontent->channel['link'];
        $feeditems   = $feedcontent->items;
        
        $output = '';

        for($iter = 0; $iter < $count && $iter < sizeof($feeditems); $iter++){
                
            $linkUri = $feeditems[$iter]['link'];
            $linkTitle = $feeditems[$iter]['title'];

            // extract the url and convert to the square version of the url
            preg_match("<img src=\"(.+?)\".*>", $feeditems[$iter]['description'], $imageUrlParts);
            $imgUrl = preg_replace("/_m.(jpg|gif|png)$/", "_s.$1", $imageUrlParts[1]);
            
            $output .= '<li><a href="' . $linkUri . '" title="' . $linkTitle . '"><img src="' . $imgUrl . '" border="0"></a></li>';
            
        }

        echo $before_widget . $before_title . '<a href="' . $siteuri . '" title="' . $title . '">' .$title . '</a><!--<a href="' . $feeduri . '"><img src="' . get_bloginfo('stylesheet_directory') . '/images/02.png"></a>-->' . $after_title;
        echo '<ul>' . $output . '</ul>';
        echo $after_widget;
        
    }

    function tm_widget_flickr_control(){
    
        $options = $newoptions = get_option( 'tm_widget_flickr' );
        
        if ( $_POST['tm_widget_flickr-submit'] ) {
        
			$newoptions['title']    = strip_tags(stripslashes($_POST['tm_widget_flickr-title']));
			$newoptions['feeduri'] = strip_tags(stripslashes($_POST['tm_widget_flickr-feeduri']));
			$newoptions['count']    = intval(strip_tags(stripslashes($_POST['tm_widget_flickr-count'])));
			
            if ( $options != $newoptions ) {
                $options = $newoptions;
		        update_option('tm_widget_flickr', $options);
            }
		}
		
		$title   = htmlspecialchars($options['title'], ENT_QUOTES);
		$feeduri = htmlspecialchars($options['feeduri'], ENT_QUOTES);
		$count   = htmlspecialchars($options['count'], ENT_QUOTES);
		
?>		
        <p><label for="tm_widget_flickr-title">Title: <input class="widefat" id="tm_widget_flickr-title" name="tm_widget_flickr-title" type="text" value="<?php echo $title; ?>" /></label></p>
        <p><label for="tm_widget_flickr-feeduri">Feed URI: <input class="widefat" id="tm_widget_flickr-feeduri" name="tm_widget_flickr-feeduri" type="text" value="<?php echo $feeduri; ?>" /></label></p>
        <p><label for="tm_widget_flickr-count">Photo count: <input class="widefat" id="tm_widget_flickr-count" name="tm_widget_flickr-count" type="text" value="<?php echo $count; ?>" /></label></p>
        <input type="hidden" id="tm_widget_flickr-submit" name="tm_widget_flickr-submit" value="1" />
<?php    
    }
    
    
    
    
    function tm_widget_listeningto( $args ){
    
        extract( $args );
        
        $options  = get_option( 'tm_widget_listeningto' );
        
        $title   = $options['title'];
        $feeduri = $options['feeduri'];
        $count   = $options['count'];
        
        $feedcontent = @fetch_rss($feeduri);
        $siteuri     = $feedcontent->channel['link'];
        $feeditems   = $feedcontent->items;
        
        $output = '';

        for($iter = 0; $iter < $count && $iter < sizeof($feeditems); $iter++){
            $output .= '<li>' . $feeditems[$iter]['description'] . '</li>';
            
        }

        echo $before_widget . $before_title . '<a href="' . $feeduri . '" title="' . $title . '">' .$title . '</a><!--<a href="' . $feeduri . '"><img src="' . get_bloginfo('stylesheet_directory') . '/images/02.png"></a>-->' . $after_title;
        echo '<ul>' . $output . '</ul>';
        echo $after_widget;
        
    }

    function tm_widget_listeningto_control(){
    
        $options = $newoptions = get_option( 'tm_widget_listeningto' );
        
        if ( $_POST['tm_widget_listeningto-submit'] ) {
        
			$newoptions['title']    = strip_tags(stripslashes($_POST['tm_widget_listeningto-title']));
			$newoptions['feeduri'] = strip_tags(stripslashes($_POST['tm_widget_listeningto-feeduri']));
			$newoptions['count']    = intval(strip_tags(stripslashes($_POST['tm_widget_listeningto-count'])));
			
            if ( $options != $newoptions ) {
                $options = $newoptions;
		        update_option('tm_widget_listeningto', $options);
            }
		}
		
		$title   = htmlspecialchars($options['title'], ENT_QUOTES);
		$feeduri = htmlspecialchars($options['feeduri'], ENT_QUOTES);
		$count   = htmlspecialchars($options['count'], ENT_QUOTES);
		
?>		
        <p><label for="tm_widget_listeningto-title">Title: <input class="widefat" id="tm_widget_listeningto-title" name="tm_widget_listeningto-title" type="text" value="<?php echo $title; ?>" /></label></p>
        <p><label for="tm_widget_listeningto-feeduri">Feed URI: <input class="widefat" id="tm_widget_listeningto-feeduri" name="tm_widget_listeningto-feeduri" type="text" value="<?php echo $feeduri; ?>" /></label></p>
        <p><label for="tm_widget_listeningto-count">Photo count: <input class="widefat" id="tm_widget_listeningto-count" name="tm_widget_listeningto-count" type="text" value="<?php echo $count; ?>" /></label></p>
        <input type="hidden" id="tm_widget_listeningto-submit" name="tm_widget_listeningto-submit" value="1" />
<?php    
    }
    
    
    
    
    function tm_widget_asides( $args ){
        extract( $args );
        
        $options  = get_option( 'tm_widget_asides' );
        
        $title    = $options['title'];
        $category = $options['category'];
        $count    = $options['count'];
        $siteuri  = get_category_link($category);
        $feeduri  = $siteuri . 'rss';
        
        $output = '';
        
        query_posts('cat=' . $category . '&posts_per_page=' . $count);
        
        echo $before_widget . $before_title . '<a href="' . $siteuri . '" title="' . $title . '">' .$title . '</a><!--<a href="' . $feeduri . '"><img src="' . get_bloginfo('stylesheet_directory') . '/images/02.png"></a>-->' . $after_title;
        
        if ( have_posts() ) {
            while ( have_posts() ) {
                the_post();
?>                
                <div id="post-<?php the_id() ?>"> 
                    <?php the_content() ?>
                    <span><a href="<?php the_permalink(); ?>" rel="bookmark" title="Permalink to <?php the_title() ?>">#</a></span>&nbsp;<span>(<?php comments_popup_link('0', '1', '%', '', '0') ?>)</span>&nbsp;<span><?php edit_post_link('e', '(', ')'); ?></span>
                </div>
<?php                
            } 
        }
        else {
?>        
            <p><?php _e('Sorry, no posts matched your criteria.') ?></p>
<?php            
        }


        echo $after_widget;
        
    }   
    
    function tm_widget_asides_control(){
    
        $options = $newoptions = get_option( 'tm_widget_asides' );
        
        if ( $_POST['tm_widget_asides-submit'] ) {
            $newoptions['title'] = strip_tags(stripslashes($_POST['tm_widget_asides-title']));
            $newoptions['category'] = strip_tags(stripslashes($_POST['tm_widget_asides-category']));
            $newoptions['count'] = strip_tags(stripslashes($_POST['tm_widget_asides-count']));
            
            if ( $options != $newoptions ) {
                $options = $newoptions;
                update_option('tm_widget_asides', $options);
            }
        }
        
        $title = htmlspecialchars($options['title'], ENT_QUOTES);
        $category = htmlspecialchars($options['category'], ENT_QUOTES);
        $count = htmlspecialchars($options['count'], ENT_QUOTES);
        
        // arguments to properly style the category drop down for us.
        $category_args = array(
            'show_option_all' => '', 
            'orderby' => 'name', 
            'order' => 'ASC', 
            'show_last_update' => 0, 
            'show_count' => 0, 
            'hide_empty' => 0, 
            'child_of' => 0, 
            'exclude' => '',
            'echo' => 1,
            'selected' => $category,
            'hierarchical' => 0,
            'name' => 'tm_widget_asides-category',
            'class' => 'widefat',
            'depth' => -1
        );

        
?>
        <p><label for="tm_widget_asides-title">Title: <input class="widefat" id="tm_widget_asides-title" name="tm_widget_asides-title" type="text" value="<?php echo $title; ?>" /></label></p>
        <p><label for="tm_widget_asides-category">Category:<?php wp_dropdown_categories($category_args) ?></label></p>
        <p><label for="tm_widget_asides-count">Asides count: <input class="widefat" id="tm_widget_asides-count" name="tm_widget_asides-count" type="text" value="<?php echo $count; ?>" /></label></p>
        <input type="hidden" id="tm_widget_asides-submit" name="tm_widget_asides-submit" value="1" />
<?php        
    } 




    function tm_widget_twitter( $args ){
    
        extract( $args );
        
        $options  = get_option( 'tm_widget_twitter' );
        
        $title     = $options['title'];
        $username  = $options['username'];
        $count     = $options['count'];
        $cache_age = $options['cache_age'];
        
        $siteuri  = 'http://www.twitter.com/' . $username . '/';
        $feeduri  = 'http://www.twitter.com/statuses/user_timeline/' . $username . '.rss';
        
        // regular expression to strip out the 'username:' 
        $regexpattern = '/^' . $username . ':/';
        
        // The fetch_rss was altered here to overload the cache timeout, default is 
        // one hour, this says $cache_age minutes.
        $feedcontent = @fetch_rss($feeduri, $cache_age*60);
                
        $feeditems   = $feedcontent->items;
        
        $output = '';

        
        for($iter = 0; $iter < $count && $iter < sizeof($feeditems); $iter++){
        
            $linkUri = $feeditems[$iter]['link'];
            $linkTitle = preg_replace($regexpattern, '', $feeditems[$iter]['title']);

            $output .= '<li>' . $linkTitle . ' <a href="' . $linkUri . '" title="' . $linkTitle . '">#</a></li>' . "\n";
        }


        echo $before_widget . $before_title . '<a href="' . $siteuri . '" title="' . $title . '">' .$title . '</a><!--<a href="' . $feeduri . '"><img src="' . get_bloginfo('stylesheet_directory') . '/images/02.png"></a>-->' . $after_title;
        echo '<ul>' . $output . '</ul>';
        echo $after_widget;
        
    }

    function tm_widget_twitter_control(){
    
        $options = $newoptions = get_option( 'tm_widget_twitter' );
        
        if ( $_POST['tm_widget_twitter-submit'] ) {
        
			$newoptions['title']    = strip_tags(stripslashes($_POST['tm_widget_twitter-title']));
			$newoptions['username'] = strip_tags(stripslashes($_POST['tm_widget_twitter-username']));
			$newoptions['count']    = intval(strip_tags(stripslashes($_POST['tm_widget_twitter-count'])));
			$newoptions['cache_age']    = intval(strip_tags(stripslashes($_POST['tm_widget_twitter-cache_age'])));
						
            if ( $options != $newoptions ) {
                $options = $newoptions;
		        update_option('tm_widget_twitter', $options);
            }
		}
		
		$title   = htmlspecialchars($options['title'], ENT_QUOTES);
		$username = htmlspecialchars($options['username'], ENT_QUOTES);
		$count   = htmlspecialchars($options['count'], ENT_QUOTES);
		$cache_age = htmlspecialchars($options['cache_age'], ENT_QUOTES); 
		
?>		
        <p><label for="tm_widget_twitter-title">Title: <input class="widefat" id="tm_widget_twitter-title" name="tm_widget_twitter-title" type="text" value="<?php echo $title; ?>" /></label></p>
        <p><label for="tm_widget_twitter-username">Username: <input class="widefat" id="tm_widget_twitter-username" name="tm_widget_twitter-username" type="text" value="<?php echo $username; ?>" /></label></p>
        <p><label for="tm_widget_twitter-count">Count: <input class="widefat" id="tm_widget_twitter-count" name="tm_widget_twitter-count" type="text" value="<?php echo $count; ?>" /></label></p>
        <p><label for="tm_widget_twitter-cache_age">Cache Age (minutes): <input class="widefat" id="tm_widget_twitter-cache_age" name="tm_widget_twitter-cache_age" type="text" value="<?php echo $cache_age; ?>" /></label></p>
        <input type="hidden" id="tm_widget_twitter-submit" name="tm_widget_twitter-submit" value="1" />
<?php     
    }
    
    
    
    
    function tm_widget_lastfm( $args ){
    
        extract( $args );
        
        $options  = get_option( 'tm_widget_lastfm' );
        
        $title     = $options['title'];
        $username  = $options['username'];
        $count     = $options['count'];
        $cache_age = $options['cache_age'];
        
        $siteuri  = 'http://www.last.fm/user/' . $username . '/';
        $feeduri  = 'http://ws.audioscrobbler.com/1.0/user/' . $username . '/recenttracks.rss';
        
        // The fetch_rss was altered here to overload the cache timeout, default is 
        // one hour, this says $cache_age minutes.
        $feedcontent = @fetch_rss($feeduri, $cache_age*60);
        
        $feeditems   = $feedcontent->items;
        
        $output = '';

        
        for($iter = 0; $iter < $count && $iter < sizeof($feeditems); $iter++){
        
            $linkUri = $feeditems[$iter]['link'];
            $linkTitle = $feeditems[$iter]['title'];

            $output .= '<li>' . $linkTitle . ' <a href="' . $linkUri . '" title="' . $linkTitle . '">#</a></li>' . "\n";
        }


        echo $before_widget . $before_title . '<a href="' . $siteuri . '" title="' . $title . '">' .$title . '</a><!--<a href="' . $feeduri . '"><img src="' . get_bloginfo('stylesheet_directory') . '/images/02.png"></a>-->' . $after_title;
        echo '<ul>' . $output . '</ul>';
        echo $after_widget;
        
    }

    function tm_widget_lastfm_control(){
    
        $options = $newoptions = get_option( 'tm_widget_lastfm' );
        
        if ( $_POST['tm_widget_lastfm-submit'] ) {
        
			$newoptions['title']    = strip_tags(stripslashes($_POST['tm_widget_lastfm-title']));
			$newoptions['username'] = strip_tags(stripslashes($_POST['tm_widget_lastfm-username']));
			$newoptions['count']    = intval(strip_tags(stripslashes($_POST['tm_widget_lastfm-count'])));
			$newoptions['cache_age'] = intval(strip_tags(stripslashes($_POST['tm_widget_lastfm-cache_age'])));
			
            if ( $options != $newoptions ) {
                $options = $newoptions;
		        update_option('tm_widget_lastfm', $options);
            }
		}
		
		$title   = htmlspecialchars($options['title'], ENT_QUOTES);
		$username = htmlspecialchars($options['username'], ENT_QUOTES);
		$count   = htmlspecialchars($options['count'], ENT_QUOTES);
		$cache_age = htmlspecialchars($options['cache_age'], ENT_QUOTES);
		
?>		
        <p><label for="tm_widget_lastfm-title">Title: <input class="widefat" id="tm_widget_lastfm-title" name="tm_widget_lastfm-title" type="text" value="<?php echo $title; ?>" /></label></p>
        <p><label for="tm_widget_lastfm-username">Username: <input class="widefat" id="tm_widget_lastfm-username" name="tm_widget_lastfm-username" type="text" value="<?php echo $username; ?>" /></label></p>
        <p><label for="tm_widget_lastfm-count">Count: <input class="widefat" id="tm_widget_lastfm-count" name="tm_widget_lastfm-count" type="text" value="<?php echo $count; ?>" /></label></p>
        <p><label for="tm_widget_lastfm-cache_age">Cache Age (minutes): <input class="widefat" id="tm_widget_lastfm-cache_age" name="tm_widget_lastfm-cache_age" type="text" value="<?php echo $cache_age; ?>" /></label></p>
        <input type="hidden" id="tm_widget_lastfm-submit" name="tm_widget_lastfm-submit" value="1" />
<?php     
    }    
    
    
    
    
    function tm_widget_elsewhere( $args ){
        echo wp_list_bookmarks('category=70&title_before=<h3>&title_after=</h3>&show_images=1&echo=0');
    }
    
    
    
       
    function tm_widgit_init(){
        if (!function_exists('register_sidebar_widget')) return;
        
        $widget_ops = array('classname' => 'tm_widget_about', 'description' => __( "thirstymind.org custom about widget") );
	    wp_register_sidebar_widget('tm_about', __('thirstymind about'), 'tm_widget_about', $widget_ops);        
        
        $widget_ops = array('classname' => 'tm_widget_search', 'description' => __( "thirstymind.org custom search widget") );
	    wp_register_sidebar_widget('tm_search', __('thirstymind search'), 'tm_widget_search', $widget_ops);
    
    	$widget_ops = array('classname' => 'tm_widget_delicious', 'description' => __( "thirstymind.org delicious widget") );
        wp_register_sidebar_widget('tm_delicious', __('thirstymind del.icio.us'), 'tm_widget_delicious', $widget_ops);
	    wp_register_widget_control('tm_delicious', __('thirstymind del.icio.us'), 'tm_widget_delicious_control' );
	    
	    $widget_ops = array('classname' => 'tm_widget_flickr', 'description' => __( "thirstymind.org flickr widget") );
        wp_register_sidebar_widget('tm_flickr', __('thirstymind flickr'), 'tm_widget_flickr', $widget_ops);
	    wp_register_widget_control('tm_flickr', __('thirstymind flickr'), 'tm_widget_flickr_control' );
	    
	    $widget_ops = array('classname' => 'tm_widget_listeningto', 'description' => __( "thirstymind.org listening to widget") );
        wp_register_sidebar_widget('tm_listeningto', __('thirstymind listening to'), 'tm_widget_listeningto', $widget_ops);
	    wp_register_widget_control('tm_listeningto', __('thirstymind listening to'), 'tm_widget_listeningto_control' );
	    
	    $widget_ops = array('classname' => 'tm_widget_asides', 'description' => __( "thirstymind.org asides widget") );
        wp_register_sidebar_widget('tm_asides', __('thirstymind asides'), 'tm_widget_asides', $widget_ops);
	    wp_register_widget_control('tm_asides', __('thirstymind asides'), 'tm_widget_asides_control' );
	    
        $widget_ops = array('classname' => 'tm_widget_twitter', 'description' => __( "thirstymind.org twitter widget") );
        wp_register_sidebar_widget('tm_twitter', __('thirstymind twitter'), 'tm_widget_twitter', $widget_ops);
	    wp_register_widget_control('tm_twitter', __('thirstymind twitter'), 'tm_widget_twitter_control' );
	    
	    $widget_ops = array('classname' => 'tm_widget_lastfm', 'description' => __( "thirstymind.org last.fm widget") );
        wp_register_sidebar_widget('tm_lastfm', __('thirstymind lastfm'), 'tm_widget_lastfm', $widget_ops);
	    wp_register_widget_control('tm_lastfm', __('thirstymind lastfm'), 'tm_widget_lastfm_control' );
	    
	    $widget_ops = array('classname' => 'tm_widget_elsewhere', 'description' => __( "thirstymind.org elsewhere links") );
	    wp_register_sidebar_widget('tm_elsewhere', __('thirstymind elsewhere'), 'tm_widget_elsewhere', $widget_ops);
	    
    }

    add_action('plugins_loaded', 'tm_widgit_init');

?>