<?php

require_once ROOT_DIR . '/RecordDrivers/RecordInterface.php';
require_once ROOT_DIR . '/RecordDrivers/GroupedWorkSubDriver.php';
require_once ROOT_DIR . '/sys/Rbdigital/RbdigitalProduct.php';
class RbdigitalRecordDriver extends GroupedWorkSubDriver {
    private $id;
    /** @var RbdigitalProduct */
    private $rbdigitalProduct;
    private $rbdigitalRawMetadata;
    private $valid;

    /** @noinspection PhpMissingParentConstructorInspection */
    /**
     * Constructor.  We build the object using all the data retrieved
     * from the (Solr) index.  Since we have to
     * make a search call to find out which record driver to construct,
     * we will already have this data available, so we might as well
     * just pass it into the constructor.
     *
     * @param   array|File_MARC_Record||string   $recordData     Data to construct the driver from
     * @access  public
     */
    public function __construct($recordId, $groupedWork = null) {
        $this->id = $recordId;

        if ($groupedWork == null){
            $this->loadGroupedWork();
        }else{
            $this->groupedWork = $groupedWork;
        }
        $this->rbdigitalProduct = new RbdigitalProduct();
        $this->rbdigitalProduct->rbdigitalId = $recordId;
        if ($this->rbdigitalProduct->find(true)) {
            $this->valid = true;
            $this->rbdigitalRawMetadata = json_decode($this->rbdigitalProduct->rawResponse);
        } else {
            $this->valid = false;
            $this->rbdigitalProduct = null;
        }
    }

    /**
     * Load the grouped work that this record is connected to.
     */
    public function loadGroupedWork() {
        require_once ROOT_DIR . '/sys/Grouping/GroupedWorkPrimaryIdentifier.php';
        require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
        $groupedWork = new GroupedWork();
        $query = "SELECT grouped_work.* FROM grouped_work INNER JOIN grouped_work_primary_identifiers ON grouped_work.id = grouped_work_id WHERE type='rbdigital' AND identifier = '" . $this->getUniqueID() . "'";
        $groupedWork->query($query);

        if ($groupedWork->N == 1){
            $groupedWork->fetch();
            $this->groupedWork = clone $groupedWork;
        }
    }

    public function getBookcoverUrl($size = 'small')
    {
        $images = $this->rbdigitalRawMetadata->images;
        foreach ($images as $image) {
            if ($image->name == 'medium' && $size == 'small') {
                return $image->url;
            }
            if ($image->name == 'large' && $size == 'medium') {
                return $image->url;
            }
            if ($image->name == 'xx-large' && $size == 'large') {
                return $image->url;
            }
        }
        return null;
    }

    public function getModule()
    {
        return 'Rbdigital';
    }

    /**
     * Assign necessary Smarty variables and return a template name to
     * load in order to display the full record information on the Staff
     * View tab of the record view page.
     *
     * @access  public
     * @return  string              Name of Smarty template file to display.
     */
    public function getStaffView()
    {
        // TODO: Implement getStaffView() method.
    }

    /**
     * Get the full title of the record.
     *
     * @return  string
     */
    public function getTitle()
    {
        $title = $this->rbdigitalProduct->title;
        $subtitle = $this->getSubtitle();
        if (strlen($subtitle) > 0) {
            $title .= ': ' . $subtitle;
        }
        return $title;
    }

    /**
     * The Table of Contents extracted from the record.
     * Returns null if no Table of Contents is available.
     *
     * @access  public
     * @return  array              Array of elements in the table of contents
     */
    public function getTableOfContents()
    {
        // TODO: Implement getTableOfContents() method.
        return array();
    }

    /**
     * Return the unique identifier of this record within the Solr index;
     * useful for retrieving additional information (like tags and user
     * comments) from the external MySQL database.
     *
     * @access  public
     * @return  string              Unique identifier.
     */
    public function getUniqueID()
    {
        return $this->id;
    }

    public function getDescription()
    {
        return $this->rbdigitalRawMetadata->shortDescription;
    }

    public function getMoreDetailsOptions()
    {
        // TODO: Implement getMoreDetailsOptions() method.
        return array();
    }

    public function getItemActions($itemInfo)
    {
        // TODO: Implement getItemActions() method.
    }

    public function getRecordActions($isAvailable, $isHoldable, $isBookable, $relatedUrls = null)
    {
        // TODO: Implement getRecordActions() method.
        return array();
    }

    /**
     * Returns an array of contributors to the title, ideally with the role appended after a pipe symbol
     * @return array
     */
    function getContributors()
    {
        // TODO: Implement getContributors() method.
        $contributors = array();
        if (isset($this->rbdigitalRawMetadata->authors)){
            $authors = $this->rbdigitalRawMetadata->authors;
            foreach ($authors as $author) {
                //TODO: Reverse name?
                $contributors[] = $author->text;
            }
        }
        if (isset($this->rbdigitalRawMetadata->narrators)){
            $authors = $this->rbdigitalRawMetadata->narrators;
            foreach ($authors as $author) {
                //TODO: Reverse name?
                $contributors[] = $author->text . '|Narrator';
            }
        }
        return $contributors;
    }

    /**
     * Get the edition of the current record.
     *
     * @access  protected
     * @return  array
     */
    function getEditions()
    {
        // No specific information provided by Rbdigital
        return array();
    }

    /**
     * @return array
     */
    function getFormats()
    {
        if ($this->rbdigitalProduct->mediaType == "eAudio") {
            return ['eAudiobook'];
        } elseif ($this->rbdigitalProduct->mediaType == "eMagazine"){
            return ['eMagazine'];
        } else {
            return ['eBook'];
        }
    }

    /**
     * Get an array of all the format categories associated with the record.
     *
     * @return  array
     */
    function getFormatCategory()
    {
        if ($this->rbdigitalProduct->mediaType == "eaudio"){
            return ['eBook', 'Audio Books'];
        } else {
            return ['eBook'];
        }
    }

    public function getLanguage()
    {
        return $this->rbdigitalProduct->language;
    }

    public function getNumHolds(){
        //TODO:  Check to see if we can determine number of holds on a title
        return 0;
    }

    /**
     * @return array
     */
    function getPlacesOfPublication()
    {
        //Not provided within the metadata
        return array();
    }

    /**
     * Returns the primary author of the work
     * @return String
     */
    function getPrimaryAuthor()
    {
        return $this->rbdigitalProduct->primaryAuthor;
    }

    /**
     * @return array
     */
    function getPublishers()
    {
        return [$this->rbdigitalRawMetadata->publisher->text];
    }

    /**
     * @return array
     */
    function getPublicationDates()
    {
        return [$this->rbdigitalRawMetadata->releasedDate];
    }

    protected function getRecordType()
    {
        return 'rbdigital';
    }

    function getRelatedRecord() {
        $id = 'rbdigital:' . $this->id;
        return $this->getGroupedWorkDriver()->getRelatedRecord($id);
    }

    public function getSemanticData() {
        // Schema.org
        // Get information about the record
        require_once ROOT_DIR . '/RecordDrivers/LDRecordOffer.php';
        $linkedDataRecord = new LDRecordOffer($this->getRelatedRecord());
        $semanticData [] = array(
            '@context' => 'http://schema.org',
            '@type' => $linkedDataRecord->getWorkType(),
            'name' => $this->getTitle(),
            'creator' => $this->getPrimaryAuthor(),
            'bookEdition' => $this->getEditions(),
            'isAccessibleForFree' => true,
            'image' => $this->getBookcoverUrl('medium'),
            "offers" => $linkedDataRecord->getOffers()
        );

        global $interface;
        $interface->assign('og_title', $this->getTitle());
        $interface->assign('og_type', $this->getGroupedWorkDriver()->getOGType());
        $interface->assign('og_image', $this->getBookcoverUrl('medium'));
        $interface->assign('og_url', $this->getAbsoluteUrl());
        return $semanticData;
    }

    /**
     * Returns title without subtitle
     *
     * @return string
     */
    function getShortTitle()
    {
        return $this->rbdigitalProduct->title;
    }

    /**
     * Returns subtitle
     *
     * @return string
     */
    function getSubtitle()
    {
        if ($this->rbdigitalRawMetadata->hasSubtitle) {
            return $this->rbdigitalRawMetadata->subtitle;
        } else {
            return "";
        }
    }

    function isValid(){
        return $this->valid;
    }
}