<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence\Product;

use App\Domain\Product\Product;
use App\Domain\Product\ProductNotFoundException;
use App\Domain\Product\ProductRepository;
use PDO;
use DateTime;

class InMemoryProductRepository implements ProductRepository
{

    private $host = "localhost";
    private $db_name = "mydb";
    private $username = "user";
    private $password = "password";
    public $conn;

    // get the database connection
    public function getConnection(){

        $this->conn = null;

        try{
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
        }catch(PDOException $exception){
            echo "Connection error: " . $exception->getMessage();
        }

        return $this->conn;
    }

    /**
     * @var Product[]
     */
    private $products;

    /**
     * InMemoryProductRepository constructor.
     *
     * @param array|null $products
     */
    public function __construct(array $products = null)
    {
        $this->products = $products ?? $this->getProducts();
    }
    /**
     * LOG data
     */

    public function logData($note = null) {

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        $date = new DateTime();
        $date->getTimestamp();
        $timestamp = time();
        $sql = "INSERT INTO oxapi_log (ip, status, note, created_at) VALUES ('$ip', '200', '$note', NOW())";
        $db = $this->getConnection();
        $db->query( $sql );

    }


    /**
     * {@inheritdoc}
     */
    public function findAll(): array
    {
        return array_values($this->products);
    }


    public function checkAuth() {
        $allow = false;
        $active = false;
        $db = $this->getConnection();
        $stmt = $db->query( "SELECT active FROM oxapi LIMIT 1" );
        $activeRes = $stmt->fetchAll( PDO::FETCH_ASSOC );
        if (count($activeRes) > 0) {
            foreach ($activeRes as $a) {
                if ($a['active'] == '1') {
                    $active = true;
                }
            }
        }
        $apiKeyList = [];
        $stmt = $db->query( "SELECT OXAPIKEY FROM oxuser" );
        $apiKeyResult = $stmt->fetchAll( PDO::FETCH_ASSOC );
        foreach ($apiKeyResult as $apiKey) {
            $apiKeyList[] = $apiKey['OXAPIKEY'];
        }

        foreach (getallheaders() as $key => $value) {
            if (strtolower($key) == 'sctaccesskey') {
                if (!in_array($value, $apiKeyList)) {
                    return false;
                }
                else {
                    if ($active) {
                        return $active;
                    }
                }
            }
        }

        return $allow;
    }


    public function getKey() {
        $accessKey = '';
        foreach (getallheaders() as $key => $value) {
            if (strtolower($key) == 'sctaccesskey') {
                $accessKey = $value;
            }
        }

        return $accessKey;
    }

    public function getProducts($getProductID = null) {
        ini_set('memory_limit', '6084M');
        ini_set('max_execution_time', '100000');
        $allow = $this->checkAuth();
        if (!$allow) {
            $this->logData('HTTP 403, Access Key: ' . $this->getKey());
            return [];
        }
//      $this->logData('HTTP 200, Access Key: ' . $this->getKey());
        $result = [];
        try {
            if ($getProductID) {
                $sql = "SELECT OXID, OXPARENTID, OXTITLE_1, OXARTNUM, OXSHORTDESC, OXWEIGHT,
            OXSTOCKLT, OXONTHEWAY,OXAVAILABILITY, OXISMETAL, OXVOLUME, OXMULTIPLE, OXNUMBER_PALLET,
            OXVARSELECT,OXMANUFACTURERID, OXPIC1, OXPIC2,OXDFILE1, OXDFILE2, OXDFILE3, OXDFILE4, OXDFILE5, OXACTIVE FROM oxarticles WHERE OXID = '$getProductID'";
            }
            else {
                $sql = "SELECT OXID, OXPARENTID, OXTITLE_1, OXARTNUM, OXSHORTDESC, OXWEIGHT,
            OXSTOCKLT, OXONTHEWAY,OXAVAILABILITY, OXISMETAL, OXVOLUME, OXMULTIPLE, OXNUMBER_PALLET,
            OXVARSELECT,OXMANUFACTURERID, OXPIC1, OXPIC2,OXDFILE1, OXDFILE2, OXDFILE3, OXDFILE4, OXDFILE5, OXACTIVE FROM oxarticles";
            }
            $db = $this->getConnection();
            $stmt = $db->query( $sql );
            $productsResult = $stmt->fetchAll( PDO::FETCH_ASSOC );
//          $db = null; // clear db object

            $categorySQL = "SELECT OXID, OXPARENTID, OXROOTID, OXTITLE, OXTITLE_1, OXTITLE_2 FROM oxcategories WHERE OXACTIVE = '1'";
            $sqlCategoryData = $db->query($categorySQL)->fetchAll();

            $index = 0;
            foreach ($productsResult as $product) {
                $row = $product;
                $oemNumber = [];
                $referenceNumber = [];
//              $details = '';
                $details = [];
                $filterType = '';
                $filterShape = '';
                $specifications = [];
                $compatibilities = [];
                $productID = $row['OXID'];
                $linkCategoryToSQL = "SELECT OXID, OXCATNID FROM oxobject2category WHERE OXOBJECTID = '$productID'";
                $sqlLinkCategoryData = $db->query($linkCategoryToSQL)->fetchAll();
                $cids = []; //category ids
                $sids = []; // sub category ids
                if (count($sqlLinkCategoryData)) {
                    $cids = [];
                    foreach ($sqlLinkCategoryData as $cl) {
                        if (strpos($cl['OXCATNID'], "lev_1") !== false) {
                            $cids[] = $cl['OXCATNID'];
                        }
                        if (strpos($cl['OXCATNID'], "lev_2") !== false) {
                            $sids[] = $cl['OXCATNID'];
                        }
                    }
                }


                $categoryName = [
                    'en' => '',
                    'ru' => '',
                    'de' => '',
                ];
                $subcategoryName = [
                    'en' => '',
                    'ru' => '',
                    'de' => '',
                ];

                if (count($cids) || count($sids)) {
                    foreach ($sqlCategoryData as $c) {
                        // limit one

                        if (count($cids)) {
                            if ($c['OXID'] == $cids[0]) {
                                $categoryName['en'] = $c['OXTITLE_1'];
                                $categoryName['ru'] = $c['OXTITLE_2'];
                                $categoryName['de'] = $c['OXTITLE'];
                            }
                        }

                        if (count($sids)) {
                            if ($c['OXID'] == $sids[0]) {
                                $subcategoryName['en'] = $c['OXTITLE_1'];
                                $subcategoryName['ru'] = $c['OXTITLE_2'];
                                $subcategoryName['de'] = $c['OXTITLE'];
                            }
                        }
                    }
                }
                $db = $this->getConnection();
                $sqlOEMQuery = "SELECT bd.title, bd.pref, bd.is_brand, oc.code_crs FROM oxd_cross as oc INNER JOIN oxd_brand_data as bd on bd.pref = oc.pref_crs WHERE bd.is_brand = '1' AND oc.product_id = '$productID'";
                $sqlReferenceQuery = "SELECT bd.title, bd.pref, bd.is_brand, oc.code_crs FROM oxd_cross as oc INNER JOIN oxd_brand_data as bd on bd.pref = oc.pref_crs WHERE bd.is_brand = '0' AND oc.product_id = '$productID'";
                $sqlDetailsQuery = "SELECT od.name, od.value FROM oxarticles as a INNER JOIN oxd_details as od on od.product_id = a.oxid WHERE a.oxid = '$productID'";
                $sqlFilterTypeQuery = "SELECT type.name as type_name FROM oxarticles as a
                                    INNER JOIN oxd_details as od on od.product_id = a.oxid
                                    INNER JOIN oxd_filter_type as type on type.id = od.filter_type_id
                                    WHERE a.oxid = '$productID' LIMIT 1";
                $sqlFilterShapeQuery = "SELECT shape.name as shape_name FROM oxarticles as a
                                    INNER JOIN oxd_details as od on od.product_id = a.oxid
                                    INNER JOIN oxd_filter_shape as shape on shape.id = od.filter_shape_id
                                    WHERE a.oxid = '$productID' LIMIT 1";
                $sqlSpecsQuery = "SELECT s.oxname, s.oxvalue FROM oxarticles as a INNER JOIN oxspecifications as s on s.OXARTICLEID = a.oxid WHERE a.oxid = '$productID'";
                $sqlOEMData = $db->query($sqlOEMQuery)->fetchAll();
                $sqlReferenceData = $db->query($sqlReferenceQuery)->fetchAll();
                $sqlDetailsData = $db->query($sqlDetailsQuery)->fetchAll();
                $sqlFilterTypeData = $db->query($sqlFilterTypeQuery)->fetchAll();
                $sqlFilterShapeData = $db->query($sqlFilterShapeQuery)->fetchAll();
                $sqlSpecsData = $db->query($sqlSpecsQuery)->fetchAll();

                $longDesc = false;
                $longDesc = $db->query("SELECT OXLONGDESC, OXLONGDESC_1, OXLONGDESC_2 FROM oxartextends WHERE OXID = '$productID'")->fetchAll();

                $sqlCompatibilitiesQuery = "SELECT * FROM oxsct_productcompatibility WHERE OXARTICLEID = '$productID'";
                $sqlCompatibilities = $db->query($sqlCompatibilitiesQuery)->fetchAll();

                if ($longDesc && isset($longDesc) && count($longDesc) > 0) {
                    $longDesc = $longDesc[0];
                }

                if (count($sqlOEMData) > 0) {
                    foreach ($sqlOEMData as $oemdata) {
                        $oemNumber[] = $oemdata['title'] . ' ' . $oemdata['code_crs'];
                    }
                }

                if (count($sqlCompatibilities) > 0) {
                    foreach ($sqlCompatibilities as $compatibilityData) {
                        $compatibilities[] = [
                            'brand' => $compatibilityData['OXBRAND'],
                            'model' => $compatibilityData['OXMODEL'],
                            'capacity' => $compatibilityData['OXVOLUME'],
                            'engine_code' => $compatibilityData['OXENGINE_CODE'],
                            'kw' => $compatibilityData['OXKW'],
                            'hp' => $compatibilityData['OXHP'],
                            'year_from' => $compatibilityData['OXYEARFROM'],
                            'year_to' => $compatibilityData['OXYEARTO'],
                        ];
                    }
                }

                if (count($sqlReferenceData) > 0) {
                    foreach ($sqlReferenceData as $refdata) {
                        $referenceNumber[] = $refdata['title'] . ' ' . $refdata['code_crs'];
                    }
                }
                if (count($sqlDetailsData) > 0) {
                    foreach ($sqlDetailsData as $detdata) {
//                      $details.= $detdata['name'] . ' - ' . $detdata['value'] . '; ';
                        $details[$detdata['name']] = $detdata['value'];
                    }
                }

                if (count($sqlFilterTypeData) > 0) {
                    foreach ($sqlFilterTypeData as $filterTypedata) {
                        $filterType.= $filterTypedata['type_name'];
                    }
                }

                if (count($sqlFilterShapeData) > 0) {
                    foreach ($sqlFilterShapeData as $filterShapedata) {
                        $filterShape.= $filterShapedata['shape_name'];
                    }
                }

                if (count($sqlSpecsData) > 0) {
                    foreach ($sqlSpecsData as $specsData) {
                        $specifications[$specsData['oxname']] = $specsData['oxvalue'];
                    }
                }

                $result[] = array(
                    "id" => $productID,
                    "name" => $row['OXTITLE_1'],
                    "parent_id" => $row['OXPARENTID'],
                    "artnum" => $row['OXARTNUM'],
                    "active" => $row['OXACTIVE'],
                    "short_desc" => $row['OXSHORTDESC'],
                    "long_desc" => [
                        'en' => $longDesc ? $longDesc['OXLONGDESC_1'] : '',
                        'ru' => $longDesc ? $longDesc['OXLONGDESC_2'] : '',
                        'de' => $longDesc ? $longDesc['OXLONGDESC'] : '',
                    ],
                    "weight" => $row['OXWEIGHT'],
                    "stock" => $row['OXSTOCKLT'],
                    "ontheway" => $row['OXONTHEWAY'],
                    "availability" => $row['OXAVAILABILITY'],
                    "is_metal" => $row['OXISMETAL'],
                    "var_select" => $row['OXVARSELECT'],
                    "volume" => $row['OXVOLUME'],
                    "multiple" => $row['OXMULTIPLE'],
                    "number_pallet" => $row['OXNUMBER_PALLET'],
                    "manufacturer_id" => $row['OXMANUFACTURERID'],
                    "picture_1" => $row['OXPIC1'] ? "https://sct-b2b.com/out/pictures/master/product/1/" . $row['OXPIC1'] : "",
                    "picture_2" => $row['OXPIC2'] ? "https://sct-b2b.com/out/pictures/master/product/2/" . $row['OXPIC2'] : "",
                    "doc_1" => !$row['OXPARENTID'] ? $row['OXDFILE1'] : '',
                    "doc_2" => !$row['OXPARENTID'] ? $row['OXDFILE2'] : '',
                    "doc_3" => !$row['OXPARENTID'] ? $row['OXDFILE3'] : '',
                    "doc_4" => !$row['OXPARENTID'] ? $row['OXDFILE4'] : '',
                    "doc_5" => !$row['OXPARENTID'] ? $row['OXDFILE5'] : '',
                    "oem_number" => $oemNumber,
                    "reference_number" => $referenceNumber,
                    "product_details" => $details,
                    "filter_types" => [
                        'type' => $filterType,
                        'shape' => $filterShape,
                    ],
                    "specifications" => $specifications,
                    "compatibilities" => $compatibilities,
                    "category" => $categoryName,
                    "subcategory" => $subcategoryName,
                );

                $index++;
            }

        } catch( PDOException $e ) {
            // show error message as Json format
            echo '{"error": {"msg": ' . $e->getMessage() . '}';
        }
        return $result;

    }

    /**
     * {@inheritdoc}
     */
    public function findProductOfId(string $id): object
    {

        $object = null;
        $productObj = null;
//      return (object) ['labas' => $this->getProducts($id)];
        $this->products = $this->getProducts($id);
        foreach($this->products as $product) {
            if ($id == $product['id']) {
                $object = $product;
                break;
            }
        }
        $object = (object) $object;
        return $object;
    }
}
