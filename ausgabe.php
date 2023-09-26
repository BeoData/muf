<?php

class MM25_Rubrik_Model
{
	public int $ID; // WP ID
	public string $title; // Wp Title Text
	public int $ArticleCounter; // Ho many Visible Articles
	public string $link; // WP Link to Tax Overview Page

	public array $aArticlesData; // Articles
	public array $aPromotionsData; //
	public int $weight; // Order Int, 1 if none is given

	public string $color; // Custom Field, Fallback to White

	public function __construct()
	{
		$this->title = '';
		$this->ArticleCounter = 0;
		$this->link = '';
		$this->weight = 0;
	}
}


// Basic Edition Model
class MM25_Edition
{
	public int $ID;// Ausgabe ID
	public string $link; // Url to the Edition
	public string $title; // Ausgabe Name
	public string $pubdate; // Publication date
	public string $image; // Cover of Ausgabe
	public string $sCurrentMediaCPTName;

	public string $sEditorialLink;

	function __construct()
	{
		// $this->ID = '';
		$this->link = '';
		$this->title = '';
		$this->pubdate = '';
		$this->image = '';
		$this->sCurrentMediaCPTName = '';
	}

	public function GetEditionOverviewData(int $sEditionID, string $current_media )
	{
		$this->ID = $sEditionID;
		$this->sCurrentMediaCPTName = $current_media;

		// Get Global Edition Options $meta_ausgabe
		$aOptionsEdition = get_option('meta_ausgabe', array());

		// Get Single Options from Meta Options
		if ( !$aOptionsEdition[$this->ID] )
		{
			// Ausgabe does not exist in options, exit
			return false;
		}

		// Set Single Options
		$aSingleOptionEdition = $aOptionsEdition[$this->ID];

		$term = get_term( $this->ID );
		//Get all Current Meta Term data by Term field and data.
		$meta_term = get_term_by( 'id', $this->ID, $term->taxonomy);

		// Store Ausgabe Data
		// Name
		$this->title = $meta_term->name;

		// Publishing Date
		if ( $aSingleOptionEdition['ausgabe_meta_date'] )
		{
			$this->pubdate = date('j.n.Y', strtotime( $aSingleOptionEdition['ausgabe_meta_date']));
		}
		// Link
		$meta_term_slug = $meta_term->slug;
		$_link = get_term_link( $meta_term->slug, $term->taxonomy );
		if ( !is_wp_error( $_link ) &&  ($_link))
		{
			$this->link = $_link;
		}

		// Get Edition Image
		$images = $aSingleOptionEdition['ausgabe_meta_image'];
		if ( $images && $images[0] != '' )
		{
			foreach ($images as $att)
			{
				// add_image_size( 'ausgabe-meta-image', 145, 9999 );
				// Width: 359px
				$src = wp_get_attachment_image_src($att, 'medium');
				$src2x = wp_get_attachment_image_src($att, 'article-width');
				$src = $src[0];
				$src2x = $src2x[0];
				$this->image = '<img src="' . $src . '" srcset="' . $src . ' 1x, ' . $src2x . ' 2x" class="shadow image "/>';
			}
		}
		return $this;
	} //GetEditionOverviewData

	public function EchoEditionTitle()
	{
		// Top Meta Cell
		echo ('<div class="grid-x meta-top">');
		echo ('<div class="cell small-12 meta-title">');
		if ( !empty( $this->link) )
		{
			echo ('<a href="'.$this->link.'">');
			echo ('<h2>' . $this->title .'</h2>');
			echo ('</a>');
		} else
		{
			echo ('<h2>' . $this->title .'</h2>');
		} // Title
		echo ('</div>'); // meta-title
		echo ('</div>'); // Top Meta Cell
	} // EchoEditionTitle

} // class MM25_Edition

// Extended Edition Model
class MM2_Ausgabe extends MM25_Edition
{
	// Boolean Debug
	private bool $_ldebug = false;

	// public int $ID; // Ausgabe ID
	// public string $title; // Ausgabe Name
	// public string $pubdate; // Puplication date
	// public string $link; // Url to the Edition


	public string $sCurrentRubrikTaxonomy;
	public string $sCurrentAusgabenTaxonomy;
	public string $sCurrentAusgabeName;

	// Is User logged in
	public bool $bLoggedInUSer;

	// Globals
	public array $aGlobalMMOptions;
	// public object $PromotionTagID; // Add global Security System, "Entgeltliche Einschaltung"
	public array $aOptionsRubrik;
	public array $aOptionsAusgaben;

	public string $focus_name;
	public string $focus_name_2;
	// public string $image; // Cover of Ausgabe
	//
	public array $ids_ausgaben;

	public array $aSingleOptionAusgabe;

	// Linked Themenheft or is Themenheft
	public bool $bIsThemenheft;
	public int $linkedThemenheftID;
	public string $term_themen_link;
	public string $linkedThemenheftName;
	public string $sThemenheftDesc;

	public WP_Term $term_themen; // ??

	public bool $bPdfOnly;
	public string $PdfOnlyText;

	public string $EditorialLink; // link to either Mediaten/CPT or Set DetailPage in Channel Options

	// Generates Pdf Html Link
	public string $meta_epaper_pdf;
	public string $sMetaPdfLink;

	// public int $meta_epaper_id;
	// public string $meta_epaper_name;
	//public string $meta_epaper_link;

	public string $ausgabe_status;
	public string $publisher;
	public string $editorship;
	public string $editorslead;
	public string $projectlead;

	//public int $ArticlesCount;
	// Articles in Rubriken
	public array $aArticles;
	// Linked Themenheft Articles
	public array $aThemenheftArticles;

	public array $aRubriken;
	public array $aMoreArticles;

	public int $iUniqueCounter;

	public string $TeaserLayout; // Option Value
	public string $Teasers; // Html Code

	function __construct()
	{
		parent::__construct();

		// Maybe move this into another class, because these are globally Set Options, and not Specific to One Ausgabe
		// Global Options
		$this->aGlobalMMOptions = get_option('mm_options', array());

		// Get Global Promotions Tag id
		//$this->PromotionTagID = get_term_by('slug', 'promotion', 'post_tag');

		// Get Global Rubrik Options $meta_rubrik
		$this->aOptionsRubrik = get_option('meta_rubrik', array()); //Returns empty array  if Option not found
		arsort($this->aOptionsRubrik);

		// Get Global Ausgabe Options $meta_ausgabe
		$this->aOptionsAusgaben = get_option('meta_ausgabe', array());
		// End Globals

		//$this->link = '';
		//$this->title = '';
		// $this->pubdate = '';
		$this->publisher = '';
		$this->editorship = '';
		$this->editorslead = '';
		$this->projectlead = '';

		$this->ausgabe_status = '';

		$this->focus_name = '';
		$this->focus_name_2 = '';

		// Only a Link to PDF File in this Edition
		$this->bPdfOnly = false;
		$this->PdfOnlyText = '';

		// Pdf File Direct link, depends if User is logged in
		$this->meta_epaper_pdf = '';
		$this->sMetaPdfLink = '';
		// $this->meta_epaper_id = '';
		// $this->meta_epaper_link = '';
		//$this->meta_epaper_name = '';

		$this->EditorialLink = '';

		// $this->ArticlesCount = 0;
		// Rubriken Array
		$this->aRubriken = array();

		// Articles, aka Posts in this Edition

		// Articles in Rubriken
		$this->aArticles = array();
		// Articles from Themenheft
		$this->aThemenheftArticles = array();
		// Articles without Rubrik
		$this->aMoreArticles = array();
		// Content Ads for this Channel
		$this->aContentAdsArticles = array();


		$this->bIsThemenheft = false;
		$this->linkedThemenheftID = -1;
		$this->sThemenheftDesc = '';
		$this->term_themen_link = '';

		$this->iUniqueCounter = 0;

		$this->TeaserLayout = 'default';
		$this->Teasers = '';
	} // Contructor

	/**
	 * @param int $sAusgabeID Ausgabe Term ID
	 * @param string $current_media Current CPT Name
	 * @param string $stype Default full
	 * 		full:  Article Details are called for One Ausgabe;
	 * 		overview: For Channel Overview, without Articles; Checks if Rubriken have articles
	 * @returns false if no Ausgabe was found
	 */
	public function MM2_GetAusgabe(int $sAusgabeID, string $current_media , string $stype = 'full' )
	{
		// Set/GEt Channel Variables
		$_Channel = new MM25_Channel();
		$_Channel->MM25_SetChannel($current_media);
		$this->sCurrentMediaCPTName = $current_media;
		$this->sCurrentAusgabenTaxonomy = $_Channel->sChannelAusgabe; // Set Name of Taxonomy for the "Ausgaben"
		$this->sCurrentRubrikTaxonomy = $_Channel->sChannelRubrik;

		// Set ID of Ausgabe
		$this->ID = $sAusgabeID;

		// Get Single Options from Meta Options
		if ( !$this->aOptionsAusgaben[$this->ID] )
		{
			// Ausgabe does not exist in options, exit
			return false;
		}
		// Set Single Options
		$this->aSingleOptionAusgabe = $this->aOptionsAusgaben[$this->ID];

		//Get all Current Meta Term data by Term field and data.
		$meta_term = get_term_by( 'id', $this->ID , $this->sCurrentAusgabenTaxonomy );
		$meta_term_slug = $meta_term->slug;
		// echo ('Meta Term:');
		// print_r ( $meta_term );
		// echo ('<br /> Meta Slug:');
		// print_r  ( $meta_term_slug);
		// echo('Single options');
		//var_dump($this->aSingleOptionAusgabe);
		// Store Ausgabe Data

		// Name
		$this->title = $meta_term->name;

		// Publishing Date
		if ( $this->aSingleOptionAusgabe['ausgabe_meta_date'] )
		{
			$this->pubdate = date('j.n.Y', strtotime( $this->aSingleOptionAusgabe['ausgabe_meta_date']));
		}

		// Link
		$_link = get_term_link( $meta_term->slug, $this->sCurrentAusgabenTaxonomy );
		if ( !is_wp_error( $_link ) &&  ($_link))
		{
			$this->link = $_link;
		}

		// Focus Titles
		$this->focus_name = $this->aSingleOptionAusgabe['ausgabe_meta_focus'] ?? '';
		$this->focus_name_2  = $this->aSingleOptionAusgabe['ausgabe_meta_focus_2'] ?? '';

		// Herausgeber
		$this->publisher = $this->aSingleOptionAusgabe['ausgabe_meta_herausgeber'] ?? '';
		// Chefredation
		$this->editorship = $this->aSingleOptionAusgabe['ausgabe_meta_chefredakteur'] ?? '';
		// Redaktionsleitung
		$this->editorslead = $this->aSingleOptionAusgabe['ausgabe_meta_redaktionsleitung'] ?? '';
		// Projektleitung
		$this->projectlead = $this->aSingleOptionAusgabe['ausgabe_meta_projektleitung'] ?? '';

		// Editorial // MediaData
		$this->EditorialLink = site_url() .'/mediadaten/#'. $current_media;
		// If a Related Detail Page ID is set in Channel Options
		if ( !empty($_Channel->sChannelDetailsPageID) )
		{
			$this->EditorialLink = get_permalink($_Channel->sChannelDetailsPageID);
		}

		// Teasers
		// From Tax Meta, Fallback: From Channel Options
		// As this is ACF, the meta is not stored in the $aSingleOptionAusgabe Object :(
		// Only if One Edition is displayed
		if ( $stype == 'full')
		{
			$termid = ('term_' . $this->ID);
			$this->TeaserLayout = "default";
			$_lTeaserLayout = get_field('teaser_layout', $termid);
			if ( !empty($_lTeaserLayout) )
			{
				$this->TeaserLayout = $_lTeaserLayout;
			}
			$_mm2_teaser_repeater = get_field('mm2_overview_channel_teasers', $termid); // Cloned from Teasers

			if ( empty($_mm2_teaser_repeater ))
			{
				// Get Teaser from Channel Options
				$_mm2_teaser_repeater = get_field($current_media . '_mm2_overview_channel_teasers', 'option');
				$_lTeaserLayout = get_field($current_media . '_teaser_layout', 'option');
				if ( !empty($_lTeaserLayout) )
				{
					// Precaution
					if ( is_array($_lTeaserLayout ) )
					{
						$this->TeaserLayout = $_lTeaserLayout[0];
					}
					else
					{
						$this->TeaserLayout = $_lTeaserLayout;
					}
				}
			}
			if ( !empty($_mm2_teaser_repeater ))
			{
				$teasers = new mm25TeasersCollectionClass();
				$teasers->maxteasercounter = 4;
				$teasers->GetTeasersData($_mm2_teaser_repeater);
			 
				$_teaserdata = mm25_getEditionTeasers($teasers->teasers, $this->TeaserLayout , $_Channel->sChannelName);
				$this->Teasers = $_teaserdata['teasercontent'];
			} // Teaser
		} // Type = full

		// Has a linked Edition, IS a Themenheft
		if (
			array_key_exists( 'ausgabe_meta_themenheft_id', $this->aSingleOptionAusgabe ) &&
			!empty( $this->aSingleOptionAusgabe['ausgabe_meta_themenheft_id'])
		)
		{
			$this->bIsThemenheft = true;
			$_linked_edition_slug = $this->aSingleOptionAusgabe['ausgabe_meta_themenheft_id'];
			$_lmeta_term = get_term_by( 'slug', $_linked_edition_slug , $this->sCurrentAusgabenTaxonomy );
			if ( !empty($_lmeta_term ))
			{
				$this->linkedThemenheftName = $_lmeta_term->name;
				//  $this->sThemenheftDesc = $this->aSingleOptionAusgabe['ausgabe_meta_themenheft_desc'];
				$_term_link = get_term_link( $_lmeta_term->term_id, $this->sCurrentAusgabenTaxonomy );
				if ( !is_wp_error( $_term_link ) )
				{
					$this->term_themen_link = $_term_link;
				}
			}
		}

		// Themenheft Linkage IF not a Themenheft, but a Themenheft links to this Edition
		if (
			$meta_term_slug &&
			!$this->bIsThemenheft
		)
		{
			foreach ( $this->aOptionsAusgaben as $ID => $value)
			{
				if ( empty($value ))
				{
					continue;
				}
				if(
					array_key_exists('ausgabe_meta_themenheft_id', $this->aOptionsAusgaben[$ID])  &&
					( $meta_term_slug == $this->aOptionsAusgaben[$ID]['ausgabe_meta_themenheft_id'])
				)
				{
					$this->linkedThemenheftID = $ID;
					$_lmeta_term = get_term_by( 'id', $this->linkedThemenheftID , $this->sCurrentAusgabenTaxonomy );
					$this->linkedThemenheftName = $_lmeta_term->name;
					$_term_link = get_term_link( $ID, $this->sCurrentAusgabenTaxonomy );
					if ( !is_wp_error( $_term_link ) )
					{
						$this->term_themen_link = $_term_link;
					}
					break;
				}
			} //endforeach for linked Themenheft
		} // If slug is set for Edition

		// Check if Ausgabe is PDF only
		if( array_key_exists('ausgabe_meta_pdfonly', $this->aSingleOptionAusgabe) )
		{
			$this->bPdfOnly = true;
			$this->PdfOnlyText = $this->aSingleOptionAusgabe['ausgabe_meta_pdfonly_text'] ?? '';
		} // endif Pdf Only

		// Status
		// Fallback, check for old and new Value
		if ( $this->aGlobalMMOptions[$this->sCurrentMediaCPTName]['status']  && !empty($this->aGlobalMMOptions[$this->sCurrentMediaCPTName]['status']) )
		{
			$this->ausgabe_status = $this->aGlobalMMOptions[$this->sCurrentMediaCPTName]['status'];
		} else
		{
			$this->ausgabe_status = get_field($current_media. '_current_status', 'option');
		}
		$this->ausgabe_status = apply_filters( 'check_cookie_status', $this->ausgabe_status );

		// Pdf Linked File
		$files = $this->aSingleOptionAusgabe['ausgabe_meta_pdf'];
		if ( $files )
		{
			foreach ($files as $att)
			{
				// $src_pdf =  wp_get_attachment_link( $att );
				$attachment_page = wp_get_attachment_url( $att );
				$filename_only = basename( get_attached_file( $att ) );

				if(stristr($attachment_page,'.pdf'))
				{
					if (
						is_user_logged_in() ||
						(strpos($attachment_page, '_pub.pdf') !== false)
					)
					{
						$eventLabel = ('Anonymous User');
						if ( is_user_logged_in())
						{
							$eventLabel = ('Registered User');
						}
						$this->meta_epaper_pdf = ('<div class="ausgabe_meta_pdf"><a href="'. $attachment_page .'" target="_blank" '. MakeOnClickTrackingCode('Magazin-Download', 'PDF ansehen', $eventLabel) .' ><i class="fas fa-file-pdf fa-lg" title="PDF ansehen"></i></i><span>PDF ansehen</span></a></div>');
						$this->sMetaPdfLink = ('<div class="ausgabe_meta_pdf"><a href="'. $attachment_page .'" download="'.$filename_only.'" target="_blank" '. MakeOnClickTrackingCode('Magazin-Download', 'PDF download', $eventLabel) .' ><i class="fas fa-download fa-lg" title="PDF herunterladen"></i><span>PDF herunterladen</span></a></div>');
					} else
					{
						$this->meta_epaper_pdf = ('<div class="ausgabe_meta_pdf"><a href="'. site_url() .'/community/registrieren/" class="mm25_login_link" '. MakeOnClickTrackingCode('Magazin-Download', 'PDF Login', 'Anonymous User') .'><i class="fas fa-file-pdf fa-lg" title="Loggen Sie sich ein um das PDF anzusehen" style="color: #CCCCCC"></i></i><span>Login: PDF ansehen</span></a></div>');
						$this->sMetaPdfLink = ('<div class="ausgabe_meta_pdf"><a href="'. site_url() .'/community/registrieren/" class="mm25_login_link" '. MakeOnClickTrackingCode('Magazin-Download', 'PDF Login', 'Anonymous User') .'><i class="fas fa-download fa-lg" title="Loggen Sie sich ein um das PDF herunterzuladen" style="color: #CCCCCC"></i><span>Login: PDF herunterladen</span></a></div>');
					}
				}
			}
			unset($files);
		} // If PDF files exist

		// Get Edition Image
		$images = $this->aSingleOptionAusgabe['ausgabe_meta_image'];
		if ( $images && $images[0] != '' )
		{
			foreach ($images as $att)
			{
				// add_image_size( 'ausgabe-meta-image', 145, 9999 );
				// Width: 359px
				$src = wp_get_attachment_image_src($att, 'medium');
				$src2x = wp_get_attachment_image_src($att, 'article-width');
				$src = $src[0];
				$src2x = $src2x[0];
				$this->image = '<img src="' . $src . '" srcset="' . $src . ' 1x, ' . $src2x . ' 2x" class="shadow image "/>';
			}
		}

		//
		// GET ARTICLES, via Rubriken
		//

		// Create Array for all "Rubriken", store id, used later for Getting Articles NOT IN these
		//
		if (
			$stype == 'full' &&
			!$this->bPdfOnly
		)
		{
			// Get ids of all Terms in the currently used "Rubriken"-Taxonomie
			$ids_rubriken = get_terms( $this->sCurrentRubrikTaxonomy, array(
					'orderby'	=> 'term_id', // default is name
					'hide_empty'	=> true,
					//'fields'		=> 'ids'
				)
			);

			$meta_rubrik = get_option('meta_rubrik');

			$array_rubriken_id = array();
			foreach ($ids_rubriken as $key => $term)
			{
				$array_rubriken_id[] = $term->term_id;
				$args_rubriken = array(
					'numberposts' => -1,
					'orderby' => 'post_date',
					'order' => 'DESC',
					'post_type' => $this->sCurrentMediaCPTName,
					'post_status' => 'publish',
					'tax_query' => array(
						'relation' => 'AND',
						array(
							'taxonomy' => $this->sCurrentAusgabenTaxonomy,
							'field' => 'id',
							'terms' => array($this->ID)
						),
						array(
							'taxonomy' => $this->sCurrentRubrikTaxonomy,
							'field' => 'id',
							'terms' => array($term->term_id)
						)
					)
				);
				$current_articles_query = get_posts($args_rubriken);

				if (count($current_articles_query) > 0)
				{
					//$this->ArticlesCount += count($current_articles_query);
					$_Rubrik = new MM25_Rubrik_Model();
					$_Rubrik->ArticleCounter = count($current_articles_query);
					$_Rubrik->ID = $term->term_id;
					$_Rubrik->link = '';
					$_Rubrik->title = $term->name;

					// TODO: what default color ?
					// Set Rubrik Background Color, for "Inhaltsverzeichniss"
					$_Rubrik->color = get_field('mm25_rubrik_cf_color', $term ) ?? '';

					// Get Weight or set to 0 if none is given
					$_Rubrik->weight = $meta_rubrik[$term->term_id]['ausgabe_meta_rubrik_gewichtung'] ?? 0;

					if ($_Rubrik->title == 'Focus' && !empty($this->focus_name))
					{
						$_Rubrik->title = ($_Rubrik->title . ': ' . $this->focus_name);
					}
					elseif ($_Rubrik->title == ' II' && !empty($this->focus_name_2))
					{
						$_Rubrik->title = ('Focus: ' . $this->focus_name_2);
					}
					// $_Rubrik->title .= (' ..');
					$link = get_term_link($term->term_id, $this->sCurrentRubrikTaxonomy);
					if (!is_wp_error($link) && ($link))
					{
						$_Rubrik->link = $link;
					}

					// For single Edition View, get all Articles in this Rubrik
					// Otherwise, in Overview of Editions only Rubriken Titles are needed

					$_lArticlesInTax = MM25_GetEditionArticles( $current_articles_query, '' ); //  Create Additional Values for a given WP Post Opject, for Overviews
					// Add only Relevant Information to Rubrik
					$_Rubrik->aArticlesData = $_lArticlesInTax;
					// Add to All Articles for Edition
					$this->aArticles = array_merge($this->aArticles, $_lArticlesInTax);

					// Add to Ausgabe, sort by Title
					$this->aRubriken[$_Rubrik->title] = $_Rubrik;

				} // endif has articles

				// Initial Sorting by Title
				ksort($this->aRubriken);
				/*if ( count($this->aRubriken ) > 1 )
				{
					usort($this->aRubriken, 'weight');
				}*/
				// Sort By Weight
				usort($this->aRubriken, function($a, $b)
				{
					$t1 = ($a->weight);
					$t2 = ($b->weight);
					return $t2 - $t1;
				});

			} // endforeach Rubriken + Articles
		}

		if ( $stype == 'full')
		{
			// Sort Articles by Post Date
			usort($this->aArticles, function($a, $b)
			{
				$t1 = strtotime($a->post_date);
				$t2 = strtotime($b->post_date);
				return $t2 - $t1;
				//return strcmp($a->post_date, $b->post_date);
			});

			// Articles without a set Taxonomie of "Rubrik" and NOT a Themenheft
			// Step 2
			//
			$args_2 = array(
				'numberposts'       => -1,
				'orderby'           => 'post_date',
				'order'         => 'DESC',
				'post_type'     =>  $this->sCurrentMediaCPTName,
				'post_status'       => 'publish',
				'tax_query' => array(
					'relation' => 'AND',
					array(
						'taxonomy' => $this->sCurrentAusgabenTaxonomy,
						'field' => 'id',
						'terms' => array( $this->ID )
					),
					array(
						'taxonomy' => $this->sCurrentRubrikTaxonomy,
						'field' => 'id',
						'terms' => $array_rubriken_id,
						'operator' => 'NOT IN'
					),
					array(
						'taxonomy' => $this->sCurrentAusgabenTaxonomy,
						'field' => 'id',
						'terms' => array( $this->linkedThemenheftID ),
						'operator' => 'NOT IN'
					)
				)
			);
			$current_artikels_query_more  = get_posts( $args_2 );
			if ( count($current_artikels_query_more) > 0 )
			{
				$this->aMoreArticles = MM25_GetEditionArticles($current_artikels_query_more,'' );
			} // Step 2

			// THEMENHEFT Articles
			// Step 3
			//
			if (
				!empty( $this->linkedThemenheftID ) &&
				!($this->bIsThemenheft)
			)
			{
				// Articles without Rubrik
				$args_themenheft = array(
					'numberposts'       => -1,
					'orderby'           => 'post_date',
					'order'         	=> 'DESC',
					'post_type'     	=> $this->sCurrentMediaCPTName,
					'post_status'       => 'publish',
					'tax_query' => array(
						array(
							'taxonomy' => $this->sCurrentAusgabenTaxonomy,
							'field' => 'id',
							'terms' => array( $this->linkedThemenheftID )
						)
					)
				);
				$articles_themenheft_query = get_posts( $args_themenheft );
				if (count($articles_themenheft_query) > 0 )
				{
					// $this->aThemenheftArticles = $articles_themenheft_query;
					$this->aThemenheftArticles = MM25_GetEditionArticles($articles_themenheft_query, '');// $this->linkedThemenheftName
				}
			} // Step 3

			// Step 4
			// Content Ads
			$args_contentads = array(
				'numberposts'   => 8,
				'orderby'       => 'post_date',
				'order'         => 'DESC',
				'post_type'     =>  $this->sCurrentMediaCPTName,
				'post_status'   => 'publish',
				'tax_query' => array(
					'relation' => 'AND',
					array(
						'taxonomy' => $this->sCurrentRubrikTaxonomy, //Rubrik Taxonomies are specific for a CPT
						'field' => 'slug', //
						'terms' => 'sponsored'
					),
				)
			);
			$query_contentads_posts  = get_posts( $args_contentads );
			if ( count($query_contentads_posts) > 0 )
			{
				$this->aContentAdsArticles = MM25_GetEditionArticles($query_contentads_posts,'' ); ;
				$this->aArticles = array_merge($this->aArticles, $this->aContentAdsArticles);
			}

		} // If Edition Data should Contain Articles, Type = full

	} //MM2_GetAusgabe

