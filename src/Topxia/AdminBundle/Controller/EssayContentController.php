<?php
namespace Topxia\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Topxia\Common\ArrayToolkit;
use Topxia\Common\Paginator; 

class EssayContentController extends BaseController
{
    public function indexAction(Request $request, $essayId)
    {
        $essay = $this->getEssayService()->getEssay($essayId);
        $category = $this->getCategoryService()->getCategory($essay['categoryId']);
        $essayContentItems = $this->getEssayContentService()->getEssayItems($essayId);

        return $this->render('TopxiaAdminBundle:EssayContent:index.html.twig', array(
            'category' => $category,
            'items' =>$essayContentItems,
            'essayId' => $essayId
        ));
    }

    public function listAction(Request $request, $essayId)
    {
        $essay = $this->getEssayService()->getEssay($essayId);
        $category = $this->getCategoryService()->getCategory($essay['categoryId']);
        $parentId = $request->query->get('parentId');
        $method = $request->query->get('method');
        $knowledgeId = $request->query->get('knowledgeId');
        $tagIds = $request->query->get('tagIds');
        $title = $request->query->get('title');

        if (empty($method)){
            $conditions = array('categoryId' => $categoryId);
        } elseif($method == 'tag'){
            $conditions = array(
                'tagIds' => $tagIds,
                'knowledgeId' => $knowledgeId,
                'categoryId' => $categoryId
            );
        } else {
            $conditions = array(
                'title' => $title,
                'knowledgeId' => $knowledgeId,
                'categoryId' => $categoryId
            );
        }

        $articleMaterialsCount = $this->getArticleMaterialService()->searchArticleMaterialsCount($conditions);

        $paginator = new Paginator($this->get('request'), $articleMaterialsCount, 8);

        $articleMaterials = $this->getArticleMaterialService()->searchArticleMaterials(
            $conditions, 
            array('createdTime','desc'),
            $paginator->getOffsetCount(),  
            $paginator->getPerPageCount()
        );

        $knowledges = $this->getKnowledgeService()->findKnowledgeByIds(ArrayToolkit::column($articleMaterials,'mainKnowledgeId'));
        $knowledges = ArrayToolkit::index($knowledges, 'id');

        return $this->render('TopxiaAdminBundle:EssayContent:content-modal.html.twig',array(
            'category' => $category,
            'parentId' => $parentId,
            'essayId' => $essayId,
            'articleMaterials' => $articleMaterials,
            'paginator' => $paginator,
            'knowledges' => $knowledges,
            'articleMaterialsCount' => $articleMaterialsCount,
            'method' => $method
        ));
    }

    public function createAction(Request $request, $essayId)
    {
        $materialIds = $request->request->get('materialIds');
        $chapterId = $request->request->get('chapterId');
        $chapterId = empty($chapterId)? '0' :substr($chapterId,'8');

        foreach ($materialIds as $materialId) {
            $fields = array(
                'essayId' => $essayId,
                'chapterId' => $chapterId,
                'materialId' => $materialId,
            );

            $this->getEssayContentService()->createContent($fields);
        }

        return $this->redirect($this->generateUrl('admin_essay_content_index', array(
                     'essayId' => $essayId,
                 )));
    }

    public function chapterCreateAction(Request $request, $essayId)
    {
        $essay = $this->getEssayService()->getEssay($essayId);
        $category = $this->getCategoryService()->getCategory($essay['categoryId']);
        $type = $request->query->get('type');
        $parentId = $request->query->get('parentId');
        $type = in_array($type, array('chapter', 'unit')) ? $type : 'chapter';

        if($request->getMethod() == 'POST'){
            $chapter = $request->request->all();
            $chapter['essayId'] = $essayId;
            $chapter = $this->getEssayContentService()->createChapter($chapter);
            return $this->render('TopxiaAdminBundle:EssayContent:list-chapter-tr.html.twig', array(
                'category' => $category,
                'essayId' => $essayId,
                'chapter' => $chapter,
            ));
        }

        return $this->render('TopxiaAdminBundle:EssayContent:chapter-modal.html.twig', array(
            'category' => $category,
            'essayId' => $essayId,
            'type' => $type,
            'parentId' => $parentId
        ));
    }

    public function editAction(Request $request, $contentId, $essayId)
    {
        if($request->getMethod() == 'POST'){
            $materialId = $request->request->all();
            $this->getEssayContentService()->updateContent($contentId, $materialId);
            return $this->createJsonResponse(true);
        }

        $essay = $this->getEssayService()->getEssay($essayId);
        $category = $this->getCategoryService()->getCategory($essay['categoryId']);
        $method = $request->query->get('method');
        $knowledgeId = $request->query->get('knowledgeId');
        $tagIds = $request->query->get('tagIds');
        $title = $request->query->get('title');

        if (empty($method)){
            $conditions = array('categoryId' => $categoryId);
        } elseif($method == 'tag'){
            $conditions = array(
                'tagIds' => $tagIds,
                'knowledgeId' => $knowledgeId,
                'categoryId' => $categoryId
            );
        } else {
            $conditions = array(
                'title' => $title,
                'knowledgeId' => $knowledgeId,
                'categoryId' => $categoryId
            );
        }

        $articleMaterialsCount = $this->getArticleMaterialService()->searchArticleMaterialsCount($conditions);

        $paginator = new Paginator($this->get('request'), $articleMaterialsCount, 8);

        $articleMaterials = $this->getArticleMaterialService()->searchArticleMaterials(
            $conditions, 
            array('createdTime','desc'),
            $paginator->getOffsetCount(),  
            $paginator->getPerPageCount()
        );

        $knowledges = $this->getKnowledgeService()->findKnowledgeByIds(ArrayToolkit::column($articleMaterials,'mainKnowledgeId'));
        $knowledges = ArrayToolkit::index($knowledges, 'id');

        return $this->render('TopxiaAdminBundle:EssayContent:content-edit-modal.html.twig',array(
            'essayId' => $essayId,
            'articleMaterials' => $articleMaterials,
            'paginator' => $paginator,
            'knowledges' => $knowledges,
            'articleMaterialsCount' => $articleMaterialsCount,
            'method' => $method,
            'contentId' => $contentId
        ));
    }

    public function chapterEditAction(Request $request, $essayId, $chapterId)
    {
        $essay = $this->getEssayService()->getEssay($essayId);
        $category = $this->getCategoryService()->getCategory($essay['categoryId']);
        $chapter = $this->getEssayContentService()->getChapter($essayId, $chapterId);
        if (empty($chapter)) {
            throw $this->createNotFoundException("章节(#{$chapterId})不存在！");
        }

        if($request->getMethod() == 'POST'){
            $fields = $request->request->all();
            $fields['essayId'] = $essayId;
            $chapter = $this->getEssayContentService()->updateChapter($essayId, $chapterId, $fields);
            return $this->render('TopxiaAdminBundle:EssayContent:list-chapter-tr.html.twig', array(
                'category' => $category,
                'essayId' => $essayId,
                'chapter' => $chapter,
            ));
        }

        return $this->render('TopxiaAdminBundle:EssayContent:chapter-modal.html.twig', array(
            'category' => $category,
            'essayId' => $essayId,
            'chapter' => $chapter,
            'type' => $chapter['type'],
        )); 
    }

    public function chapterDeleteAction(Request $request, $essayId, $chapterId)
    {
        $this->getEssayContentService()->deleteChapter($essayId, $chapterId);
        return $this->createJsonResponse(true);
    }

    public function deleteAction(Request $request, $essayId, $contentId)
    {
        $this->getEssayContentService()->deleteContent($essayId, $contentId);
        return $this->createJsonResponse(true);
    }

    public function sortAction(Request $request, $essayId)
    {
        $ids = $request->request->get('ids');
        if(!empty($ids)){
            $this->getEssayContentService()->sortEssayItems($essayId, $request->request->get('ids'));
        }
        return $this->createJsonResponse(true);
    }

    private function getCategoryService()
    {
        return $this->getServiceKernel()->createService('Taxonomy.CategoryService');
    }

    private function getEssayContentService()
    {
        return $this->getServiceKernel()->createService('EssayContent.EssayContentService');
    }

    private function getKnowledgeService()
    {
        return $this->getServiceKernel()->createService('Taxonomy.KnowledgeService');
    }

    private function getArticleMaterialService()
    {
        return $this->getServiceKernel()->createService('ArticleMaterial.ArticleMaterialService');
    }

    private function getEssayService()
    {
        return $this->getServiceKernel()->createService('Essay.EssayService');
    }
}