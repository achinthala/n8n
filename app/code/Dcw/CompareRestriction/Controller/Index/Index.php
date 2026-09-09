<?php
namespace Dcw\CompareRestriction\Controller\Index;

use Magento\Framework\App\ResourceConnection;
class Index extends \Magento\Framework\App\Action\Action
{
	protected $_pageFactory;

    private ResourceConnection $resourceConnection;

	public function __construct(
		\Magento\Framework\App\Action\Context $context,
		\Magento\Framework\View\Result\PageFactory $pageFactory,
        ResourceConnection $resourceConnection
    )
	{
		$this->_pageFactory = $pageFactory;
        $this->resourceConnection=$resourceConnection;
		return parent::__construct($context);
	}

	public function execute()
	{        
        $tableName = $this->resourceConnection->getTableName('magefan_blog_post');
        //Initiate Connection
        $connection = $this->resourceConnection->getConnection();        
        $sql = "Select content,post_id FROM " . $tableName." WHERE `content` LIKE '%[button%'";
        $result = $connection->fetchAll($sql); 
        if($result) {
            foreach ($result as $key => $data) {
                $content=$data['content'];
                $post_id=$data['post_id'];
                $pattern ="/\[button(.*?)]/"; 
                // $content=str_replace('’',"'",$content);   
                $content = str_replace('&#8217;', "'", $content);  
                // $content = str_replace('"', "'", $content);           
                preg_match_all($pattern, $content, $matches);
                $btnCode = array();
                foreach ($matches as $key => $value) {
                    if ($key % 2 == 0) {
                        $btnCode = $value;
                    }
                }
                
               $pattern='/(?<=link=)(.*)(?=\[\/button\])/im';
                preg_match_all($pattern, $content, $matched);
                $data=[];
                foreach($matched as $value) {
                    foreach($value as $key=>$val) {
                        $d=explode(']',$val);
                        if(count($d)<=2) {
                            $html="<a style='color: #EAF0F5;font-size: 0.875rem;line-height: 1.313rem;height: 52px;text-decoration: none;background: #6993B6;padding: 0.625rem 1rem;' href='".$d[0]."'>";
                            $content=str_replace( $btnCode[$key],$html,$content);  
                        } else {
                            $html="<a style='color: #EAF0F5;font-size: 0.875rem;line-height: 1.313rem;height: 52px;text-decoration: none;background: #6993B6;padding: 0.625rem 1rem;' href='".$d[0]."'>";
                            $content=str_replace($btnCode[$key],$html,$content);
                            $pattern='/\\[[^\\]]*/ism';
                            preg_match_all($pattern, $d[2], $matches);
                            foreach($matches as $value) {
                                foreach($value as $index=>$val) {
                                    $data=explode('link=',$val);
                                    if(count($d)>1) {
                                         $link=$d[1];  
                                         $html="<a style='color: #EAF0F5;font-size: 0.875rem;line-height: 1.313rem;height: 52px;text-decoration: none;background: #6993B6;padding: 0.625rem 1rem;' href='".$link."'>";
                                        $content=str_replace($val,$html,$content); 
                                    } 
                                 }
                            }
                        }
                    }
                }
                
                $content=str_replace("[button color=blue size=small link=https://www.rubberflooringinc.com/samplerequest.aspx?utm_source=blog&amp;utm_medium=post&amp;utm_campaign=Outdoor%20Trends]","<a style='color: #EAF0F5;font-size: 0.875rem;line-height: 1.313rem;height: 52px;text-decoration: none;background: #6993B6;padding: 0.625rem 1rem;' href='https://www.rubberflooringinc.com/samplerequest.aspx?utm_source=blog&utm_medium=post&utm_campaign=Home%20Gym%20BG'>",$content); 
                $content=str_replace("[/button]","</a>",$content);
                $content=str_replace(']',"",$content);
                 $connection->update(
                    $connection->getTableName($tableName),
                    ['content'=>$content],
                    ['post_id = ?' => (int)$post_id]
                );

                // die("!11111");
            }
        }
        
        die("Sdsdsd");
        return $this->_pageFactory->create();
	}
}
?>