<?php

/**
 *	assets/MM25/page-ausgabe.php
 *
 *	Main Template for an Edition
 *	Displays Articles with Tag: Promotion only for logged-in Users
 *
 * @author      fnsch
 * @version     0.0.8
 * @since       2013-04-17
 * changes:
 *      2023-07-12 Ignore Rubrik "Sponsored" in the Table of Content - "Inhaltsverzeichniss"
 *      2023-05-30 Added Google Ads Positioning
 *      2022-08-11 Added new Custom Dimension for Edition
 *      2022-05-05 Updated PdfOnly Editions Layout
 *      2021-04-14 Updated Sidebar Banner Code
 *      - 2020-12-16 Added New A-Side Banner Code
 *      - 2019-07-25 DMO: PDF-Icon-Check nach Name: if ( is_user_logged_in() || (strpos($attachment_page, '_pub.pdf') !== false) )
 *
 */

$_ldebug = false;
if ( $_is_user = is_user_logged_in() )
{
	$current_user = wp_get_current_user();
	if ( $current_user->ID == '166' &&
		isset($_GET['debug'])
	)
	{
		$_ldebug = true;
	}
}

// Current CPT
global $current_media;
$current_media = '';

// Set Currently Active Nav Element ( MM25_GetEditionNav()  ) for Editions
global $page_menu_active; //
$page_menu_active = '';

// Set Template Source Directory
$_template_directory = '/assets/MM25/';

// Get Current Edition Terminology
// Get Current Ausgabe Taxonomy
// Set Current Media from that Taxonomy
$term = get_term_by( 'slug', get_query_var( 'term' ), get_query_var( 'taxonomy' ) );
$term_taxonomy = get_taxonomy( $term->taxonomy );
if ( $term_taxonomy->object_type )
{
	$current_media = $term_taxonomy->object_type['0'];
}

if ( !empty($current_media) )
{
	// Set Channel Data
	$_Channel = new MM25_Channel();
	$_Channel->MM25_SetChannel($current_media);
	// Set Template Path from Channel
	$_template_directory  = $_Channel->sChannelTemplatePath;

	$_Ausgabe = new MM2_Ausgabe();

	// Get Set Edition
	$_acf_current_edition = get_field($_Channel->sChannelCPT.'_current-edition','option');
	if( $_acf_current_edition == $term->term_id  )
	{
		// Current Edition
		$_Ausgabe->MM2_GetAusgabe( $_acf_current_edition, $_Channel->sChannelCPT);
		$page_menu_active = 'current';
	}
	else
	{
		// Get Edition ID from Post Object
		$_Ausgabe->MM2_GetAusgabe( $term->term_id, $_Channel->sChannelCPT );
		$page_menu_active = '';
	}
}

// Define global var before the call to the header
// add Edition Name for Ga and Matomot tracking
global $global_edition_name;
$global_edition_name = $_Ausgabe->title;

// Header
locate_template($_template_directory . '/header.php', true);

if ( $_ldebug )
{
	echo ('<pre>');
	echo ('<strong>Template:</strong> page-ausgabe.php<br />');
	echo ('<strong>Current Edition:</strong> '.$_Ausgabe->title .'<br />');
	echo ('</pre>');
}
?>
<div id="main" class="channel-edition site-main <?php echo ( $current_media ); ?>" role="main" itemscope itemtype="http://www.schema.org/MedicalScholarlyArticle">
    <div class="grid-container">
        <div class="grid-x">
            <div class="cell">
                <?php
                echo ( MM25_GetEditionNav(  $_Channel, $page_menu_active, ''));
                ?>
            </div><?php // cell ?>

            <?php
            //
            // Edition Output
            //
            ?>
            <div class="medium-12 cell">
                <div class="grid-x grid-margin-x">
                    <?php
                    echo ('<div class="cell ausgabe ' .$_Ausgabe->ID .'">');
                    echo ('<div class="grid-x grid-margin-x grid-margin-y articles">');

                    // Teasers
                    // Leave empty div if not teaser are available, so Edition Meta Box is always on the right side
                    // Pdf Only Edition
                    if ( $_Ausgabe->bPdfOnly  === true )
                    {
                        EchoEditionMeta($_Ausgabe);
                        //  echo ('<div class="grid-x grid-margin-x grid-margin-y pdf-only">');
                        echo ('<div class="cell small-12 medium-6 pdf-only">');
                        echo ($_Ausgabe->PdfOnlyText);
                        echo ('<p>Sie k&ouml;nnen diese Ausgabe als PDF Version ansehen und lesen: </p>');
                        echo ('' . $_Ausgabe->meta_epaper_pdf . '  '. $_Ausgabe->sMetaPdfLink . '');
                        echo ('</div>');
                        // echo ('</div>');
                        echo ('<div class="clearfix">&nbsp;</div>');
                        echo (' <br />');
                    }
                    else
                    {
                        echo ('<div class="cell small-12 medium-6 ">');
                        if ( !empty( $_Ausgabe->Teasers ) )
                        {
                            echo( $_Ausgabe->Teasers );
                        }
                        echo ( '</div>'); // Teasers

                        //Edition Meta Box
                        EchoEditionMeta($_Ausgabe);
                    }
                    echo ('</div>'); // articles

                    // This Edition is NOT a Pdf Only Edition
                    // Lists All Articles
                    //
                    if ( !$_Ausgabe->bPdfOnly )
                    {
                        // Content Ads
                        /*
                        if ( !empty($_Ausgabe->aContentAdsArticles))
                        {
                            echo ('<br />');
                            echo ('<div class="grid-x grid-margin-x grid-margin-y articles">');
                            // Output: Articles with Fixed Positions
                            MM2_EchoArticles($_Ausgabe->aContentAdsArticles);
                            echo ('<div class="clearfix">&nbsp;</div>');
                            echo ('</div>'); // end Articles
                            echo ('<br />');
                        } // Content Ads
                        */

                        // Google Ads Positioning Helpers, only three can be used for one Page
                        global $banner_slot_gam_sl01;
                        $banner_slot_gam_sl01 = true;
                        global $banner_slot_gam_sl02;
                        $banner_slot_gam_sl02 = true;
                        global $banner_slot_gam_sl03;
                        $banner_slot_gam_sl03 = true;

                        // Articles with Rubrik
                        // Sort by Order
                        if ( !empty($_Ausgabe->aArticles))
                        {
                            echo ('<br />');
                            echo ('<div class="grid-x grid-margin-x grid-margin-y articles">');
                            // Output: Articles with Fixed Positions
                            MM2_EchoArticles($_Ausgabe->aArticles);
                            echo ('<div class="clearfix">&nbsp;</div>');
                            echo ('</div>'); // end Articles
                            echo ('<br />');
                        } // Articles with Rubrik

                        // Articles without Rubrik
                        if ( !empty( $_Ausgabe->aMoreArticles ))
                        {
                            echo ('<br />');
                            echo ('<h3>Weitere Artikel</h3>');
                            echo ('<div class="grid-x grid-margin-x grid-margin-y articles">');
                            MM2_EchoArticles($_Ausgabe->aMoreArticles);
                            echo ('<div class="clearfix">&nbsp;</div>');
                            echo ('</div>'); // end More Articles
                        }

                        // Linked Themenheft
                        if ( !empty( $_Ausgabe->aThemenheftArticles ))
                        {
                            $linked_thmID=$_Ausgabe->linkedThemenheftID;

                            // echo ('<br />');
                            // echo ('<h3>Themenheft: '. $_Ausgabe->linkedThemenheftName.'</h3>');

                            if (!empty($_Ausgabe->linkedThemenheftID)) {

                                // Initialize a new MM2_Ausgabe object
                                $_ThemeEdition = new MM2_Ausgabe();

                                if (is_array($_Ausgabe->linkedThemenheftID)) {
                                    foreach ($_Ausgabe->linkedThemenheftID as $linked_ID) {

                                        // Populate object with data
                                        $_ThemeEdition->MM2_GetAusgabeMeta($linked_ID, $current_media);
                                        echo('<a id="themenheft_' . $linked_ID . '" class="anchor"></a>');

                                        // Output meta information for each linked theme edition
                                        echo('<div class="grid-x grid-margin-x grid-margin-y articles">');
                                        EchoEditionMeta($_ThemeEdition);

                                        // Output articles for this specific theme edition
                                        if (!empty($_ThemeEdition->aArticles)) {
                                            MM2_EchoArticles($_ThemeEdition->aArticles);
                                        }

                                        echo('<div class="clearfix">&nbsp;</div>');
                                        echo('</div>'); // end articles for this theme edition
                                    }

                                    // If you still want to output articles common to all theme editions, you can do so here
                                    // MM2_EchoArticles($_Ausgabe->aThemenheftArticles);
                                }
                            }


/*
                            var_dump($_ThemeEdition);
                            die();*/
                        }

                      echo ('<br />');
                    }
                    ?>
                </div><?php  // Ausgabe Wrapper ?>
            </div><?php // End Edition Output ?>

        </div><?php // grid-x ?>
        <br/>
    </div><?php // grid-container ?>
<?php
/**
 * Table of Content - Sorted by Taxonomy
 */
if ( !empty($_Ausgabe->aRubriken))
{
    ?>
    <div id="overviewModal" class="reveal small" data-reveal style="position:relative;">


        <div class="closebuttonwrapper">
            <div class="closebtn" data-close>
                <span>Schließen</span><i class="fal fa-times"></i>
            </div>
        </div>
        <div class="grid-container">
            <div class="grid-x">
                <div class="cell small-12 medium-10 medium-offset-1">
                    <?php
					echo ('<h2>Inhaltsverzeichnis ' . $_Ausgabe->title. '</h2>');
					echo ('<ul>');
					foreach ( $_Ausgabe->aRubriken as $title => $rubrik ) // Terms
					{
                        // Ignore the Term: "Sponsored"
                        if ( $rubrik->title == 'Sponsored' )
                        {
                            continue;
                        }
						echo ('<li>');
						$_BGColorStyle = '';

                        /*
						if ( $rubrik->color && !empty($rubrik->color) )
						{
							//$_BGColorStyle = ' style="background-color: '.$rubrik->color.'"; ';

						}
                        $_BGColorStyle = ' style="background-color: #f00"  ';

  echo '<div class="modal-header"> <div class="gray-background paidactivation-notice"  >Entgeltliche Einschaltung</div>
</div>';
                        */




                        if ($rubrik->color) {
                            // Check if the rubrik color is set
                            $_BGColorStyle = ' style="background-color: ' . $rubrik->color . '"; ';
                        } else {
                            // Use a default background color if rubrik color is not set
                            $_BGColorStyle = ' style="background-color: var(--PrimaryColor)"; ';
                        }
						$_BGColorStyle = ' style="background-color: var(--PrimaryColor)"; '; // Temp Fallback
                        echo ('<span class="rubrik" data-weight="'.$rubrik->weight.'" '.$_BGColorStyle.'>'.$rubrik->title.'</span>');
						if ( $rubrik->aArticlesData )
						{

							foreach ( $rubrik->aArticlesData as $ID => $Article )
							{

                             //   var_dump($lArticleIsPaidactivation);
                               // $lArticleInfoTitle = get_field('mm2_infotitle', $lArticle->ID);

                             //  var_dump($rubrik->color);
								/**
                                 * @TODO
                                 * Alt title:
                                 *  Check for mm2_infotitle/mm2_infotitle_alt and ifset and not empty, echo it, link in bold
                                 * like the code in post-article.php
                                 *
                                // Infotitle
								$lArticleInfoTitle = get_field('mm2_infotitle', $lArticle->ID);
								// Alternative Infotitle  - lol
								$lArticleInfoTitleAlt = get_field('mm2_infotitle_alt', $lArticle->ID);
								if ($lArticleInfoTitle && !$lArticleInfoTitleAlt)
								{
								$lArticleInfoTitle = '<div class="article_infotitle">' . $lArticleInfoTitle . '</div>';
								}
								else if ( $lArticleInfoTitleAlt )
								{
								$lArticleInfoTitle = '<div class="article_infotitle">' . $lArticleInfoTitleAlt . '</div>';
								}**/
                              //  var_dump  ( $rubrik->ID  );
                                /**
                                $mm2_infotitle = get_field('mm2_infotitle',$Article->ID);
                                $mm2_infotitle_alt = get_field('mm2_infotitle_alt',$Article->ID);
                               // echo('<br>');
                                echo ('<div style="height: 4px "></div>');
                                 //  echo '<div style="font-size: 18px;">'. $mm2_infotitle .'</div>';
                                //   echo '<div style="font-size: 18px;"><a href="'.get_permalink($Article->ID).'">'. $mm2_infotitle .'</a></div>';
                                echo '<div>'. $mm2_infotitle_alt .'</div>';
                                // echo '<div style="font-size: 18px;"><a href="'.get_permalink($Article->ID).'">'. $mm2_infotitle_alt .'</a></div>';
                                **/
                                $lArticleIsPaidactivation = get_field('mm2_paidactivation', $ID) == '1';
                                if ($lArticleIsPaidactivation)
                                {
                                    echo '<div class="modal-header">
                                    <div class="gray-background paidactivation-notice">Entgeltliche Einschaltung</div>
                                    </div>';
                                }
                                echo ('<div class="single-article">');
                                $ArticleInfoTitle=  mm25_getArticleInfoTitle($Article->ID);
                                echo $ArticleInfoTitle;
                               
                                //	echo ('<a href="'.get_permalink($Article->ID).'">'.$Article->post_title.'</a>');
                                echo ('<strong ><a href="'.get_permalink($Article->ID).'">'.$Article->post_title.'</a></strong>');
                                echo ('<div class="authors">'.$Article->authors.'</div>');
								echo ('</div>');

							}
						}
                        echo ('</li>');
					} // endforeach Rubriken with Articles

					// Articles without Terms
					if ( !empty( $_Ausgabe->aMoreArticles ))
					{
						echo ('<li>');
						echo ('<span class="rubrik">Weitere Artikel</span><br />');
						foreach ($_Ausgabe->aMoreArticles as $ID => $post )
						{
							echo ('<a href="'.get_permalink($post->ID).'">'.$post->post_title.'</a>');
							echo ('<div class="authors">'.$post->authors.'</div>');
						} // endforeach Article
						echo ('</li>');
					}

					// Themenheft - Linked Theme Edition
					if ( !empty( $_Ausgabe->aThemenheftArticles ))
					{
						echo ('<li>');
						echo ('<span class="rubrik">Themenheft: '. $_Ausgabe->linkedThemenheftName.'</span><br />');
						foreach ($_Ausgabe->aThemenheftArticles as $ID => $post )
						{

							echo ('<a href="'.get_permalink($post->ID).'">'.$post->post_title.'</a>');
							echo ('<div class="authors">'.$post->authors.'</div>');
						} // endforeach Article
						echo ('</li>');
					} // endif - has  a linked Themed Edition
					echo ('</ul>');
					?>
                </div>
            </div>
        </div>
    </div>
	<?php
} // End -  Table of Content

// A-Side Banner - Google Ad System
echo (MM2_GenerateSkyscraperCode('gam_sky01'));

?>
    </div><?php // grid-container ?>
<?php
// MuFusli from Global Channel Options
PrintMufusliFromOptions($current_media);
PrintMufusliRepeaterFromOptions($current_media);
?>
    </div><?php // #Main channel-edition ?>
<?php
// Footer
locate_template($_template_directory . '/footer.php', true);
?>
<style>
    .article_infotitle{ padding-top: 5px;}
    .single-article { padding-bottom: 5px; }
    .rubrik { margin-bottom: 5px; display: inline-block;  }

     .gray-background {

        margin-bottom: 5px;
        display: inline-block;
        background-color: #EEEEEE;
        color: #595959;
        padding: 0px 5px;
        font-size: 0.7em;
         line-height: 1.875rem;
    }
</style>
