<?php
namespace Topxia\AdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Topxia\Common\ArrayToolkit;
use Topxia\Common\Paginator;

class  EssayController extends BaseController 
{

    public function indexAction (Request $request,$categoryId)
    {
        $category = $this->getCategoryService()->getCategory($categoryId);

        if (empty($category)) {
            throw $this->createNotFoundException("分类(#{$categoryId})不存在，创建课件失败！");
        }

        $fields = $request->query->all();

        $conditions = array(
            'title'=>''
        );

        if(!empty($fields['status'])){
            $conditions =$fields;
        }

        $paginator = new Paginator(
            $this->get('request'),
            $this->getEssayService()->searchEssaysCount($conditions),
            20
        );

        $essays = $this->getEssayService()->searchEssays(
            $conditions,
            array('createdTime', 'DESC'),
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );
        $ids = ArrayToolkit::column($essays,'userId');
        $users = $this->getUserService()->findUsersByIds($ids);
        return $this->render('TopxiaAdminBundle:Essay:index.html.twig', array(
            'category' => $category,
            'essays' => $essays ,
            'users' => $users,
            'paginator' => $paginator
        ));
    }

    public function createAction(Request $request ,$categoryId)
    {
        $category = $this->getCategoryService()->getCategory($categoryId);
        if ($request->getMethod() == 'POST') {

            $formData = $request->request->all();
            $formData['categoryId'] = $categoryId;
            $this->getEssayService()->createEssay($formData);

            return $this->redirect($this->generateUrl('admin_essay_manage',array('categoryId'=>$categoryId)));
        }
        return $this->render('TopxiaAdminBundle:Essay:modal.html.twig',array(
            'category' => $category
        ));
    }

    public function editAction(Request $request,$categoryId, $id)
    {
        $category = $this->getCategoryService()->getCategory($categoryId);

        $essay = $this->getEssayService()->getEssay($id);

        if (empty($essay)) {
            $this->createNotFoundException('已经删除或者未发布！');
        }

        if ($request->getMethod() == 'POST') {

            $formData = $request->request->all();
            $formData['categoryId'] = $categoryId;
            $this->getEssayService()->updateEssay($id,$formData);

            return $this->redirect($this->generateUrl('admin_essay_manage',array('categoryId'=>$categoryId)));
        }
        return $this->render('TopxiaAdminBundle:Essay:modal.html.twig',array(
            'category' => $category,
            'essay' => $essay
        ));
    }

    public function publishAction(Request $request,$id)
    {
        $this->getEssayService()->publishEssay($id);
        return $this->createJsonResponse(true);
    }

    public function unpublishAction(Request $request,$id)
    {
        $this->getEssayService()->unpublishEssay($id);
        return $this->createJsonResponse(true);
    }

    public function deleteAction(Request $request)
    {
        $ids= $request->request->get('ids',array());
        $id = $request->request->get('id',null);

        if ($id) {
            array_push($ids,$id);
        }
        $result = $this->getEssayService()->deleteEssaysByIds($ids);

        if ($result) {
            return $this->createJsonResponse(array('status' => 'success'));
        } else {
            return $this->createJsonResponse(array('status' => 'fail'));
        }
    }

    public function showAction(Request $request, $id)
    {
        $essay = $this->getEssayService()->getEssay($id);
        return $this->render('TopxiaAdminBundle:Essay:show-modal.html.twig', array(
            'essay' => $essay
            ));
    }

    private function getCategoryService()
    {
        return $this->getServiceKernel()->createService('Taxonomy.CategoryService');
    }

    private function getEssayService()
    {
        return $this->getServiceKernel()->createService('Essay.EssayService');
    }
}