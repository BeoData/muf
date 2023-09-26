
	/**
	 * @param int $sAusgabeID Ausgabe Term ID
	 * @param string $current_media Current CPT Name
	 * @returns false if no Ausgabe was found
	 */
	public function MM2_GetAusgabeMeta(int $sAusgabeID, string $current_media )
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

		// Name
		$this->title = $meta_term->name;

		// Editorial // MediaData
		$this->EditorialLink = site_url() .'/mediadaten/#'. $current_media;
		// If a Related Detail Page ID is set in Channel Options
		if ( !empty($_Channel->sChannelDetailsPageID) )
		{
			$this->EditorialLink = get_permalink($_Channel->sChannelDetailsPageID);
		}

		// Herausgeber
		$this->publisher = $this->aSingleOptionAusgabe['ausgabe_meta_herausgeber'] ?? '';

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

		// Has a linked Ausgabe, aka IS a Themenheft
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
				$this->sThemenheftDesc = $this->aSingleOptionAusgabe['ausgabe_meta_themenheft_desc'] ?? '';
				$_term_link = get_term_link( $_lmeta_term->term_id, $this->sCurrentAusgabenTaxonomy );
				if ( !is_wp_error( $_term_link ) )
				{
					$this->term_themen_link = $_term_link;
				}
			}
		}

		// Themenheft Linkage IF not a themenheft, but a Themenhaft links to this Edition
		if (
			$meta_term_slug &&
			!$this->bIsThemenheft
		)
		{
            /*
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

*/


            foreach ($this->aOptionsAusgaben as $ID => $value) {
                if (empty($value)) {
                    continue;
                }
                if (
                    array_key_exists('ausgabe_meta_themenheft_id', $this->aOptionsAusgaben[$ID]) &&
                    ($meta_term_slug == $this->aOptionsAusgaben[$ID]['ausgabe_meta_themenheft_id'])
                ) {
                    $_lmeta_term = get_term_by('id', $ID, $this->sCurrentAusgabenTaxonomy);
                    if ($_lmeta_term) {
                        $this->linkedThemenheftID[$_lmeta_term->name] = $ID;
                        $this->linkedThemenheftName = $_lmeta_term->name;
                        $_term_link = get_term_link($ID, $this->sCurrentAusgabenTaxonomy);
                        if (!is_wp_error($_term_link)) {
                            $this->term_themen_link = $_term_link;
                        }
                    }
                }
            }



		} // If slug is set for Edition

		// Check if Ausgabe is PDF only
		if( array_key_exists('ausgabe_meta_pdfonly', $this->aSingleOptionAusgabe) )
		{
			$this->bPdfOnly = true;
			$this->PdfOnlyText = $this->aSingleOptionAusgabe['ausgabe_meta_pdfonly_text'] ?? '';

		} // endif Pdf Only

		// Status
		$this->ausgabe_status = $this->aGlobalMMOptions[$this->sCurrentMediaCPTName]['status'];
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
						$this->meta_epaper_pdf = ('<div class="ausgabe_meta_pdf"><a href="'. $attachment_page .'" target="_blank" '. MakeOnClickTrackingCode('Magazin-Download', 'PDF ansehen', $eventLabel) .'><i class="fas fa-file-pdf fa-lg"></i></i><span>PDF ansehen</span></a></div>');
						$this->sMetaPdfLink = ('<div class="ausgabe_meta_pdf"><a href="'. $attachment_page .'" download="'.$filename_only.'" target="_blank" '.MakeOnClickTrackingCode('Magazin-Download', 'PDF download',$eventLabel) .'><i class="fas fa-download fa-lg"></i><span>PDF herunterladen</span></a></div>');
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

	} //MM2_GetAusgabeMeta

	public function MM25_SetDebugMode( $bool )
	{
		$this->_ldebug = $bool;
	}

} // Class MM2_Ausgabe

/**
 * @desc Returns All Set Current Editions in Channel Options in an Array
 * @desc Sorted by PubDate of Edition
 * @return array  Sorted Array
 */
function MM25_GetAllCurrentEditions() :array
{
	$_Channels = new MM25_Channel();
	$_CurrentChannels = $_Channels->MM25_GetAllChannelNames(); // Get all Channels
	$_aReturn = array();

	foreach ($_CurrentChannels as $_cpt_name)
	{
		if ( $_cpt_name == DIGITALDOCTOR_CPT_BASE_NAME ){
			continue;
		}
		// Get If a Current Edition is set for Channel
		$_lCurrentEditionID = get_field($_cpt_name . '_current-edition', 'option');
		if (!empty($_lCurrentEditionID))
		{
			// Init small Edition Class
			$_Edition = new MM25_Edition();
			$_lCurrentEdition = $_Edition->GetEditionOverviewData($_lCurrentEditionID, $_cpt_name);
			$_aReturn[$_lCurrentEditionID] = $_lCurrentEdition;
		}
	} // endforeach

	// Sort Editions by pubdate Date
	usort($_aReturn, function ($a, $b)
	{
		$t1 = strtotime($a->pubdate);
		$t2 = strtotime($b->pubdate);
		return $t2 - $t1;
		//return strcmp($a->post_date, $b->post_date);
	}); // endusort

	return ($_aReturn);
} // MM25_GetAllCurrentEditions


/**
 * @desc
 * @return void // echo
 */
function EchoEditionMeta( $Edition)
{
	//
	// Edition Meta
	//
	echo ('<div class="small-12 medium-6 cell">');
	echo ('<div class="grid-x edition-meta">');

	// Image and Meta-Icons
	echo ('<div class="cell small-4 edition-image">');
	if ( !empty( $Edition->image) )
	{
		echo ( $Edition->image );
	}
	//  Meta-Icons
	echo ('<div class="grid-x icons-list">');
	echo ('<div class="cell small-3 meta-link text-center">');
	// Inhaltsverzeichiss Link
	if ( !$Edition->bPdfOnly  &&  !empty($Edition->aRubriken) )
	{
		echo ('<div class="ausgabe_meta_info">');
		echo('<a href="#" data-open="overviewModal" title="Inhaltsverzeichnis"><i class="fas fa-list-alt fa-lg" ></i><span>Inhaltsverzeichnis 2</span></a>');
		echo('</div>');
	}
	echo ('</div>');
	echo ('<div class="cell small-3 meta-link text-center">');
	// Editorial Link

	if ( 'pocket-guide' != $Edition->sCurrentMediaCPTName  &&
		$Edition->EditorialLink
	)
	{
		echo ('<div class="ausgabe_meta_info">');
		echo('<a href="'. $Edition->EditorialLink .'" title="Mediadaten"><i class="fas fa-info-square fa-lg"></i><span>Info</span></a>');
		echo('</div>');
	}
	echo ('</div>');
	// PDF
	echo ('<div class="cell small-3 meta-link text-center">');
	if ( $Edition->meta_epaper_pdf )
	{
		echo ( $Edition->meta_epaper_pdf );
	}
	echo ('</div>');
	// PDF download
	echo ('<div class="cell small-3 meta-link text-center">');
	if ( $Edition->sMetaPdfLink )
	{
		echo ( $Edition->sMetaPdfLink );
	}
	echo ('</div>'); // Pdf-link
	echo ('</div>'); //icons-list
	echo ('</div>'); // edition-image and Meta-icons

	echo ('<div class="cell small-8">');
	// Top Meta Cell
	echo ('<div class="grid-x meta-top">');
	echo ('<div class="cell small-12 meta-title">');
	if ( !empty( $Edition->link) )
	{
		echo ('<a href="'.$Edition->link.'">');
		echo ('<h2>' . $Edition->title .'</h2>');
		echo ('</a>');
	} else
	{
		echo ('<h2>' . $Edition->title .'</h2>');
	} // Title
	echo ('</div>'); // meta-title
	echo ('</div>'); // Top Meta Cell

	echo ('<div class="meta-wrapper">');
	if ( !empty( $Edition->pubdate ))
	{
		echo ('<p>');
		echo ('<strong>Erscheinungsdatum:</strong><br />');
		echo ($Edition->pubdate);
		echo ('</p>');
	}// Pub.Data

	// Link to Editorial Page or old Mediadata Page
	if (
		'pocket-guide' != $Edition->sCurrentMediaCPTName &&
		$Edition->EditorialLink
	)
	{
		echo ('<p class="editorial_link">');
		echo('<a href="'. $Edition->EditorialLink.'" ><strong>Mediadaten</strong> &raquo;</a>');
		echo('</p>');
	}

	// Link to Overview Popup
	if ( !$Edition->bPdfOnly  &&  !empty($Edition->aRubriken) )
	{
		echo ('<p class="overview_link">');
		echo('<a href="#" data-open="overviewModal"><strong>Inhaltsverzeichnis</strong> &raquo;</a>');
		echo('</p>');
	}
	// Publisher
	if ( !empty ($Edition->publisher ))
	{
		echo ('<p>');
		echo ('<strong>Herausgeber:</strong><br />');
		echo($Edition->publisher);
		echo ('</p>');
	}
/*
	// Themenheft Anchor Link
	if ( $Edition->term_themen_link )
	{
		// Themenheft Desc
		if ( $Edition->sThemenheftDesc )
		{
			echo ('<p>');
			echo( $Edition->sThemenheftDesc);
			echo ('</p>');
		}
		echo ('<p class="theme_link">');
		if ( !$Edition->bIsThemenheft )
		{
			echo ('<strong>Themenheft:</strong><br />');
			echo('<a href="#themenheft" >'. $Edition->linkedThemenheftName.'</a>');
		}
		else
		{
			echo('<a href="'.$Edition->term_themen_link.'" >'. $Edition->linkedThemenheftName.'</a>');
		}
		echo ('</p>');
	}
*/

   // var_dump($Edition->term_themen_link, $Edition->linkedThemenheftName);

    // Themenheft Anchor Link
    if ($Edition->term_themen_link) {
        // Themenheft Desc
        if ($Edition->sThemenheftDesc) {
            echo ('<p>');
            echo ($Edition->sThemenheftDesc);
            echo ('</p>');
        }
        echo ('<p class="theme_link">');

        if (!$Edition->bIsThemenheft) {
            echo ('<strong>Themenheft:</strong><br />');
            if (is_array($Edition->linkedThemenheftName)) {
                foreach ($Edition->linkedThemenheftName as $name) {
                    echo('<a href="#themenheft" >' . $name . '</a><br>');
                }
            } else {
                echo('<a href="#themenheft" >' . $Edition->linkedThemenheftName . '</a>');
            }
        } else {
            if (is_array($Edition->term_themen_link) && is_array($Edition->linkedThemenheftName)) {
                foreach ($Edition->term_themen_link as $index => $link) {
                    $name = isset($Edition->linkedThemenheftName[$index]) ? $Edition->linkedThemenheftName[$index] : 'Unknown';
                    echo('<a href="' . $link . '" >' . $name . '</a><br>');
                }
            } else {
                echo('<a href="' . $Edition->term_themen_link . '" >' . $Edition->linkedThemenheftName . '</a>');
            }
        }

        echo ('</p>');
    }

    echo ('</div>'); // cell
	echo ('</div>'); // edition meta wrapper
	echo ('</div>'); // edition meta wrapper
	echo ('</div>'); // cell
} // EchoEditionMeta

/**
 * @param string $_sCptName
 * @return string
 */
function MM2_EchoEditionCover(string $_sCptName): string
{
	$sReturn = '';
	// Get If a Current Edition is set for Channel
	$_lCurrentEditionID = get_field( $_sCptName.'_current-edition', 'option' );
	if ( !empty($_lCurrentEditionID))
	{
		// Init small Edition Class
		$_Edition = new MM25_Edition();
		$CurrentEdition = $_Edition->GetEditionOverviewData($_lCurrentEditionID, $_sCptName);
		if (!empty( $CurrentEdition->image ))
		{
			$sReturn = $CurrentEdition->image;
		}
	}
	return $sReturn;
} // MM2_EchoEditionCover
