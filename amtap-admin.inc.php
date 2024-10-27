<?php
	// Associate texts
	$aAmtapLocales = array(
		'ca' => array(
			'locale' => __( 'Canada', 'amtap' ),
			'url' => 'https://associates.amazon.ca/',
			'title_get_id' => __( 'Get Associate ID for Amazon.ca', 'amtap' ),
			'title_set_default' => __( 'Set Canada as default', 'amtap' )
		),
		'fr' => array(
			'locale' => __( 'France', 'amtap' ),
			'url' => 'https://partenaires.amazon.fr/',
			'title_get_id' => __( 'Get Associate ID for Amazon.fr', 'amtap' ),
			'title_set_default' => __( 'Set France as default', 'amtap' )
		),
		'de' => array(
			'locale' => __( 'Germany', 'amtap' ),
			'url' => 'http://partnernet.amazon.de/',
			'title_get_id' => __( 'Get Associate ID for Amazon.de', 'amtap' ),
			'title_set_default' => __( 'Set Germany as default', 'amtap' )
		),
		'jp' => array(
			'locale' => __( 'Japan', 'amtap' ),
			'url' => 'https://affiliate.amazon.co.jp/',
			'title_get_id' => __( 'Get Associate ID for Amazon.co.jp', 'amtap' ),
			'title_set_default' => __( 'Set Japan as default', 'amtap' )
		),
		'uk' => array(
			'locale' => __( 'United Kingdom', 'amtap' ),
			'url' => 'https://affiliate-program.amazon.co.uk/',
			'title_get_id' => __( 'Get Associate ID for Amazon.co.uk', 'amtap' ),
			'title_set_default' => __( 'Set United Kingdom as default', 'amtap' )
		),
		'us' => array(
			'locale' => __( 'United States', 'amtap' ),
			'url' => 'http://affiliate-program.amazon.com/',
			'title_get_id' => __( 'Get Associate ID for Amazon.com', 'amtap' ),
			'title_set_default' => __( 'Set United States as default', 'amtap' )
		)
	);
	$aAmtapLocales = AMTAP::sort_locales( $aAmtapLocales );
?>

<!-- Display error or confirmation messages -->
<?php if ( $aMessage ) {
	$sInputInvalid = ( strstr( $aMessage[0], 'invalid' ) || strstr( $aMessage[0], 'empty' ) ) ? ' aria-invalid="true"' : '';
?>
	<!-- feedback message -->
	<div id="amtap-feedback" class="<?php echo $aMessage[0]; ?> fade">
		<p><strong><?php echo $aMessage[1]; ?></strong></p>
	</div>
<?php } ?>

<!-- Wrapper creating an admin box -->
<div id="amtap" class="wrap">
	<!-- Headline -->
	<h2><?php _e( 'Amazon Machine Tags Configuration', 'amtap' ); ?></h2>
	
	<form action="options.php" method="post" id="amtap-conf">
		<h3><?php _e( 'How to use it', 'amtap' ); ?></h3>
		<!-- Plugin description -->
		<ol>
			<li><?php _e( 'Get your own Amazon Web Services <a href="http://aws-portal.amazon.com/gp/aws/developer/account/index.html?action=access-key">Access Key Identifyers</a>.', 'amtap' ); ?></li>
			<li><?php _e( 'Create a <code>wp-content/cache/</code> directory with permissions set to 755, or 777 if you create the directory as <code>root</code> user.', 'amtap' ); ?></li>
			<li><?php _e( 'Put <code>&lt;?php AMTAP::get_items(); ?&gt;</code> in your sidebar and start using machine tags.', 'amtap' ); ?></li>
			<li><?php _e( 'Edit <code>amtap-blog.css</code> if you like.', 'amtap' ); ?></li>
			<li><?php _e( 'If you want to use tags in a blog article, write <code>[amtap book:isbn=1234567890]</code> or <code>[amtap amazon:asin=1234567890]</code>.', 'amtap' ); ?></li>
			<li><?php _e( 'If you have any further questions, check the description and comments on the <a href="http://learningtheworld.eu/2009/amazon-authorization/">plugin homepage</a>.', 'amtap' ); ?></li>
		</ol>
		
		<!-- AWS access key -->
		<h3><?php _e( 'Required settings', 'amtap' ); ?></h3>
			
		<fieldset>
			<!-- Hidden fields supporting the WordPress options functions -->
			<?php wp_nonce_field('update-options') ?>
			<!-- Subheadline -->
			<h4><?php _e( 'AWS Access Key Identifiers', 'amtap' ); ?></h4>
			<p class="state"><?php _e( '(required)', 'amtap' ); ?></p>
			
			<ul class="locales">
				<li>
					<!-- AWS access key field -->
					<label for="aws_access_key_id" class="locale"><?php _e( 'Access Key ID', 'amtap' ); ?></label>
					<input id="aws_access_key_id" name="aws_access_key_id" type="text" size="24" maxlength="20" value="<?php echo get_option( 'aws_access_key_id' ); ?>" aria-required="true"<?php echo $sInputInvalid; ?> />
					<!-- Link to AWS site -->
					<span>(<a href="http://aws-portal.amazon.com/gp/aws/developer/account/index.html?action=access-key" title="<?php _e('About the AWS Access Key'); ?>"><?php _e( 'What is this?', 'amtap' ); ?></a>)</span>
				</li>
				<li>
					<!-- AWS key secret field -->
					<label for="aws_access_key_secret" class="locale"><?php _e( 'Secret Access Key', 'amtap' ); ?></label>
					<input id="aws_access_key_secret" name="aws_access_key_secret" type="text" size="40" maxlength="40" value="<?php echo get_option( 'aws_access_key_secret' ); ?>" aria-required="true"<?php echo $sInputInvalid; ?> />
				</li>
			</ul>
			
			<!-- Submit button -->
			<p class="submit"><input type="submit" id="amtap-submit-1" name="Submit" value="<?php _e( 'Update options &raquo;', 'amtap' ); ?>" /></p>
		</fieldset>
		
		
		<!-- Optional settings -->
		<h3><?php _e( 'Optional settings', 'amtap' ); ?></h3>
		
		<!-- Basic Configuration -->
		<fieldset>
			<!-- Subheadline -->
			<h4><?php _e( 'Basic Configuration', 'amtap' ); ?></h4>
			<p class="state"><?php _e( '(optional)', 'amtap' ); ?></p>
			<!-- Items -->
			<ul class="locales">
				<li>
					<!-- Headline -->
					<label for="defaultHeadline" id="defaultHeadlineLabel" class="locale"><?php _e( 'Sidebar headline', 'amtap' ); ?></label>
					<input type="text" id="defaultHeadline" name="amtap_headline" size="30" maxlength="255" value="<?php echo get_option( 'amtap_headline' ); ?>" />
				</li>
				<li>
					<!-- Link Target -->
					<span class="indent"><?php _e( 'Link target', 'amtap' ); ?></span>
					<input type="radio" class="radio first" id="linkTargetSame" name="amtap_target" value="same"<?php checked( 'same', get_option( 'amtap_target') ); ?> />
					<label for="linkTargetSame"><?php _e( 'Same window (recommended)', 'amtap' ); ?></label>
					<input type="radio" class="radio" id="linkTargetNew" name="amtap_target" value="new"<?php checked( 'new', get_option( 'amtap_target') ); ?> />
					<label for="linkTargetNew"><?php _e( 'New window', 'amtap' ); ?></label>
				</li>
			</ul>
			<p>
				<!-- Rating -->
				<input type="checkbox" id="rating" class="checkbox" name="amtap_rating" value="true"<?php checked( 'true', get_option( 'amtap_rating' ) ); ?> />
				<label for="rating"><?php _e( 'Show stars for average rating', 'amtap' ); ?></label>
			</p>
		</fieldset>
		
		<!-- Associate IDs -->
		<fieldset>
			<!-- Subheadline -->
			<h4><?php _e( 'Amazon Associate IDs', 'amtap' ); ?></h4>
			<p class="state"><?php _e( '(optional)', 'amtap' ); ?></p>
			<!-- Description -->
			<p><?php _e( 'As an Associate you earn commissions by referring sales to Amazon. For each locale you need to <strong>register separately</strong>. You can use automatic <a href="http://www.google.com/language_tools">translation tools</a> if you can&rsquo;t read the local content. You can leave fields empty if you have no Associate ID for a particular locale.', 'amtap' ); ?></p>
			<p><?php _e( 'If you have the <strong><a href="http://priyadi.net/archives/2005/02/25/wordpress-ip-to-country-plugin/">IP to Country</a></strong> plugin installed, a visitor gets the link to the nearest Amazon shop supporting her preferred language. Otherwise you probably know where most of your visitors come from and can set the default locale here.', 'amtap' ); ?></p>
			
			<ul class="locales">
				<?php
					foreach ( $aAmtapLocales as $key => $value ) {
						$sCode = $aAmtapLocales[$key]['code']; echo "\n";
				?>
					<li>
						<!-- Associate ID <?php echo $aAmtapLocales[$key]['locale']; ?> -->
						<label for="l-<?php echo $sCode; ?>" class="locale"><?php echo $aAmtapLocales[$key]['locale']; ?></label>
						<input type="text" id="l-<?php echo $sCode; ?>" name="amtap_associate_id_<?php echo $sCode; ?>" size="16" maxlength="20" value="<?php echo get_option( 'amtap_associate_id_' . $sCode ); ?>" />
						<!-- Link to Amazon Associate page -->
						(<a href="<?php echo $aAmtapLocales[$key]['url']; ?>" title="<?php echo $aAmtapLocales[$key]['title_get_id']; ?>"><?php _e( 'Get ID', 'amtap' ); ?></a>)
						<!-- Set as default -->
						<input type="radio" id="l-<?php echo $sCode; ?>-def" class="radio" name="amtap_associate_default" value="<?php echo $sCode; ?>"<?php checked( $sCode, get_option( 'amtap_associate_default') ); ?> title="<?php echo $aAmtapLocales[$key]['title_set_default']; ?>" />
						<label for="l-<?php echo $sCode; ?>-def"><?php _e( 'set as default', 'amtap' ); ?></label>
					</li>
				<?php } echo "\n"; ?>
			</ul>
			
			<?php if ( function_exists( 'wp_ip2c_getCountryCode2' ) ) { ?>
				<!-- IP to Country -->
				<p>
					<input type="checkbox" id="ip2country" class="checkbox" name="amtap_ip2country" value="true"<?php checked( 'true', get_option( 'amtap_ip2country' ) ); ?> />
					<label for="ip2country"><?php _e( 'Use IP to Country to override the default if available', 'amtap' ); ?></label>
				</p>
			<?php } ?>
		</fieldset>
		
		<!-- Default tags on every page -->
		<fieldset>
			<!-- Subheadline -->
			<h4><?php _e( 'Default Tags', 'amtap' ); ?></h4>
			<p class="state"><?php _e( '(optional)', 'amtap' ); ?></p>
			<!-- Description -->
			<p><?php _e( 'Some people, like book authors, want links to featured items in the sidebar on <em>every</em> page, including the home page. Enter a comma separated list of machine tags (<code>book:isbn=1234567890</code>) here if you want that feature. Otherwise leave it empty.', 'amtap' ); ?></p>
			<p>
				<label for="defaultTags" id="defaultsLabel"><?php _e( 'Default tags', 'amtap' ); ?></label>
				<input type="text" id="defaultTags" name="amtap_default_tags" size="30" maxlength="255" value="<?php echo get_option( 'amtap_default_tags' ); ?>" />
			</p>
		</fieldset>
		
		<!-- Configuration options -->
		<fieldset>
			<!-- Subheadline -->
			<h4><?php _e( 'Advanced Configuration', 'amtap' ); ?></h4>
			<p class="state"><?php _e( '(optional)', 'amtap' ); ?></p>
			<p><?php _e( 'The <a href="https://affiliate-program.amazon.com/gp/advertising/api/detail/agreement.html">Amazon Product Advertising <acronym>API</acronym></a> license allows requests from your blog every second and limits caching to 1 hour. So the <strong>maximum cache age</strong> should be anything between 1 and 3600 seconds.', 'amtap' ); ?></p>
			<p><?php _e( 'Don&rsquo;t change the <strong>ResponseGroup</strong> parameters unless you fully understand the concept and are prepared to adapt the <acronym title="Extensible Stylesheet Language Transformation">XSLT</acronym> as well.', 'amtap' ); ?></p>
			
			<ul class="config-options">
				<li>
					<!-- Maximum cache age -->
					<label for="maxage" ><?php _e( '<abbr title="Maximum">Max.</abbr> cache age', 'amtap' ); ?></label>
					<input type="text" id="maxage" name="amtap_xml_maxage" size="4" maxlength="4" value="<?php echo get_option( 'amtap_xml_maxage' ); ?>" />
				</li>
				<li>
					<!-- Response group -->
					<label for="groups"><?php _e( 'Response Group', 'amtap' ); ?></label>
					<input type="text" id="groups" name="amtap_item_response_group" size="16" maxlength="100" value="<?php echo get_option( 'amtap_item_response_group' ); ?>" />
					(<a href="http://docs.amazonwebservices.com/AWSECommerceService/2009-07-01/DG/index.html?ItemLookup.html" title="<?php _e( 'About the ResponseGroup parameter', 'amtap' ); ?>"><?php _e( 'What is this?', 'amtap' ); ?></a>)
				</li>
			</ul>
		</fieldset>

		<!-- Support the plugin author ;) -->
		<fieldset>
			<!-- Subheadline -->
			<h4><?php _e( 'Donate Amazon Karma', 'amtap' ); ?></h4>
			<p class="state"><?php _e( '(optional)', 'amtap' ); ?></p>
			<!-- Description -->
			<p><?php _e( 'If you like this plugin and don&rsquo;t have Associate IDs for all locales, you could support <a href="http://learningtheworld.eu/2009/amazon-authorization/">the author</a> by letting his IDs fill in the blanks:', 'amtap' ); ?></p>
			<p>
				<input type="checkbox" id="donation" class="checkbox" name="amtap_donation" value="true"<?php checked( 'true', get_option( 'amtap_donation' ) ); ?> />
				<label for="donation"><?php _e( 'Donate unused Amazon credits to the author', 'amtap' ); ?></label>
			</p>
		</fieldset>
		
		<!-- Debug options -->
		<fieldset>
			<h4><?php _e( 'Debugging', 'amtap' ); ?></h4>
			<p class="state"><?php _e( '(optional)', 'amtap' ); ?></p>
			
			<p><?php _e( 'If you check the <strong>debug option</strong>, there will be a commented <acronym title="Uniform Resource Locator">URL</acronym> in a post&rsquo;s source code. Paste it into your browser&rsquo;s address bar, replace &quot;<code>&amp;amp;</code>&quot; with &quot;<code>&amp;</code>&quot; and get plain <acronym title="Extensible Markup Language">XML</acronym> reponses for debugging.', 'amtap' ); ?></p>
			<p>
				<input type="checkbox" id="debug" class="checkbox" name="amtap_debug" value="true"<?php checked( 'true', get_option( 'amtap_debug') ); ?> />
				<label for="debug"><?php _e( 'Debug option', 'amtap' ); ?></label>
			</p>
		
			<!-- Submit button -->
			<p class="submit"><input type="submit" id="amtap-submit-2" name="Submit" value="<?php _e( 'Update options &raquo;', 'amtap' ); ?>" /></p>
			<!-- Hidden fields supporting the WordPress options functions -->
			<input type="hidden" name="action" value="update" />
			<!-- List of all field names in this form -->
			<input type="hidden" name="page_options" value="aws_access_key_id, aws_access_key_secret, amtap_headline, amtap_target, amtap_rating, amtap_xml_maxage, amtap_item_response_group, amtap_debug, amtap_associate_id_ca, amtap_associate_id_fr, amtap_associate_id_de, amtap_associate_id_jp, amtap_associate_id_uk, amtap_associate_id_us, amtap_associate_default, amtap_ip2country, amtap_default_tags, amtap_donation" />
		</fieldset>
	</form>
</div>