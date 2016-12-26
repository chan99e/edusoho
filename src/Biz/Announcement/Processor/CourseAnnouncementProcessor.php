<?php
namespace Biz\Announcement\Processor;

use Topxia\Service\Common\ServiceKernel;
use Biz\User\Service\NotificationService;

class CourseAnnouncementProcessor extends AnnouncementProcessor
{
    public function checkManage($targetId)
    {
        try {
            $this->getCourseService()->tryManageCourse($targetId);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function checkTake($targetId)
    {
        return $this->getCourseService()->canTakeCourse($targetId);
    }

    public function getTargetShowUrl()
    {
        return 'course_show';
    }

    public function announcementNotification($targetId, $targetObject, $targetObjectShowUrl)
    {
        $count   = $this->getCourseService()->getCourseStudentCount($targetId);
        $members = $this->getCourseMemberService()->findCourseStudents($targetId, 0, $count);

        $result = false;
        if ($members) {
            $message = array('title' => $targetObject['title'],
                'url'                    => $targetObjectShowUrl,
                'type'                   => 'course');
            foreach ($members as $member) {
                $result = $this->getNotificationService()->notify($member['userId'], 'learn-notice', $message);
            }
        }

        return $result;
    }

    public function tryManageObject($targetId)
    {
        $course = $this->getCourseService()->tryManageCourse($targetId);

        return $course;
    }

    public function getTargetObject($targetId)
    {
        return $this->getCourseService()->getCourse($targetId);
    }

    public function getShowPageName($targetId)
    {
        $canTake = $this->checkTake($targetId);

        if ($canTake) {
            return 'announcement-show-modal.html.twig';
        } else {
            return 'announcement-course-nojoin-show-modal.html.twig';
        }
    }

    public function getActions($action)
    {
        $config = array(
            'create' => 'AppBundle:Course/CourseAnnouncement:create',
            'edit'   => 'AppBundle:Course/CourseAnnouncement:edit',
            'show'   => 'AppBundle:Course/CourseAnnouncement:show'
        );

        return $config[$action];
    }

    protected function getCourseService()
    {
        return ServiceKernel::instance()->getBiz()->service('Course:CourseService');
    }

    protected function getCourseMemberService()
    {
        return ServiceKernel::instance()->getBiz()->service('Course:MemberService');
    }

    /**
     * @return NotificationService
     */
    protected function getNotificationService()
    {
        return ServiceKernel::instance()->createService('User:NotificationService');
    }
}
