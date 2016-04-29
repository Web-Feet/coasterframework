<?php namespace CoasterCms\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class PageVersionSchedule extends Eloquent
{
    protected $table = 'page_versions_schedule';

    private $_now;

    public static function checkPageVersionIds()
    {
        $now = (new \DateTime)->format('Y-m-d H:i:s');
        $pageVersionSchedules = self::where('live_from', '<=', $now)->orderBy('live_from')->get();

        $newVersions = [];

        if (!$pageVersionSchedules->isEmpty()) {

            $pageVersionSchedulesArr = [];
            foreach ($pageVersionSchedules as $pageVersionSchedule) {
                $pageVersionSchedulesArr[$pageVersionSchedule->page_version_id] = $pageVersionSchedule;
            }

            $pageVersionIdToPageVersion = [];
            $pageVersions = PageVersion::whereIn('id', array_keys($pageVersionSchedulesArr))->get();
            foreach ($pageVersions as $pageVersion) {
                $pageVersionIdToPageVersion[$pageVersion->id] = $pageVersion;
            }

            $pages = [];
            foreach ($pageVersionSchedulesArr as $pageVersionId => $pageVersionSchedule) {
                if (!isset($pages[$pageVersionIdToPageVersion[$pageVersionId]->page_id])) {
                    $pages[$pageVersionIdToPageVersion[$pageVersionId]->page_id] = [];
                }
                $pages[$pageVersionIdToPageVersion[$pageVersionId]->page_id][$pageVersionId] = $pageVersionSchedule;
            }

            foreach ($pages as $page_id => $pageVersionSchedules) {
                $pageVersionIdToPublish = end($pageVersionSchedules)->page_version_id;
                $pageVersionToPublish = $pageVersionIdToPageVersion[$pageVersionIdToPublish];
                if (!empty($pageVersionToPublish)) {
                    $pageVersionToPublish->publish(false, true);
                    $newVersions[$page_id] = $pageVersionToPublish->version_id;
                }
                foreach ($pageVersionSchedules as $pageVersionSchedule) {
                    $pageVersionSchedule->delete();
                }
            }

        }

        return $newVersions;
    }

    public function delete()
    {
        $this->_now = new \DateTime;
        $newDate = new \DateTime($this->live_from);
        $this->repeat($newDate);

        if ($this->live_from != $this->getOriginal('live_from')) {
            $newPageVersionSchedule = $this->replicate();
            $newPageVersionSchedule->live_from = $this->live_from;
            $newPageVersionSchedule->save();
        }

        return parent::delete();
    }

    private function repeat($newDate)
    {
        if ($this->repeat_in) {
            $newDate->modify('+'.$this->repeat_in.' seconds');
        } elseif ($this->repeat_in_func) {
            switch ($this->repeat_in_func) {
                case 'm':
                    $currentDay = '0';
                    $newDay = (int) $newDate->format('d');
                    $newDateClone = clone $newDate;
                    $i = 1;
                    while ($currentDay != $newDay) {
                        $newDateClone = clone $newDate;
                        $currentDay = (int) $newDateClone->format('d');
                        $newDateClone->modify('+'.$i++.' month');
                        $newDay = (int) $newDateClone->format('d');
                    }
                    $newDate = clone $newDateClone;
                break;
            }
        }

        $this->live_from = $newDate->format('Y-m-d H:i:s');
        if (($this->repeat_in || $this->repeat_in_func) && $this->_now > $newDate) {
            $this->repeat($newDate);
        }
    }

    public static function restore($obj)
    {
        $obj->save();
    }

}