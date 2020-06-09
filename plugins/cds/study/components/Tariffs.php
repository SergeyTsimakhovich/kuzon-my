<?php namespace Cds\Study\Components;

use Cds\Study\Models\Bill;
use Cds\Study\Models\BillProperty;
use Cds\Study\Models\Organization;
use Cds\Study\Models\Program;
use Cds\Study\Models\Sector;
use Cds\Study\Models\Service;
use Cds\Study\Models\Tariff;
use Cds\Study\Models\TariffCostPeriod;

use Auth;
use Config;
use Session;
use Flash;
use Carbon\Carbon;

use Renatio\DynamicPDF\Classes\PDF;

class Tariffs extends ComponentBase
{
    private $classPath = ['Organization' => Organization::class, 'Program' => Program::class];

    public function onRun()
    {
        $this->addCss("/plugins/cds/study/components/tariffs/assets/tariff.css");
        $this->addJs("/plugins/cds/study/components/tariffs/assets/tariff.js");
    }

    public function componentDetails()
    {
        return [
            'name'        => 'Тарифы',
            'description' => 'No description provided yet...'
        ];
    }

    public function defineProperties()
    {
        return [
            'classObject' => [
                'title'             => 'Класс объекта для выбора тарифа',
                'description'       => 'Может принимать значения Organization и Program',
                'default'           => 'Organization',
                'type'              => 'string',
            ]
        ];
    }

    /**
     * Вариант отрисовки выбранных тарифных планов (которые ещё не оплачены или ещё не закончились)
     * @return array
     */
    public function onRenderCurrentTariffs()
    {
        $currentTariffsList = $this->getCurrentTariffsList();
        $historyTariffsList = $this->getHistoryTariffsList();

        return compact('currentTariffsList', 'historyTariffsList');
    }

    /**
     * Метод для получения ссылки на выставленный счет в формате PDF.
     * @return bool|string
     */
    public function onGetPdfBill()
    {
        $data = $this->getBillData(post('bill_id'));

        if (empty($data)) return false;
        return $this->getPdfBill($data);
    }

    /**
     * Вариант отрисовки для страницы Финансовые операции (таблица со всеми оплаченными счетами).
     * @return array
     */
    public function onRenderFinanceList()
    {
        $date['start'] = Carbon::now()->startOfMonth();
        $date['end']   = Carbon::now();

        $financeList = $this->getFinanceList();

        return compact('financeList', 'date');
    }

    /**
     * Метод вызывается, когда меняют период на странице Финансовые операции
     * @return array
     */
    public function onChangeDate()
    {
        $financeList = $this->getFinanceList(post());

        return ['#finance_list' => $this->renderPartial('@finance_list_table', compact('financeList'))];
    }

    /**
     * Вариант отрисовки тарифов (название, стоимость, сроки действия, услуги и кнопка выбора).
     * Для Приоритетного размещения рисуется ещё формочка с выпадающими списками для поиска нужной программы.
     * @return array
     */
    public function onRenderChoice()
    {
        $sectorMain   = $this->getSectorMainList(); //Для приортетного размещения
        $periodList   = $this->getTariffCostPeriodList();
        $servicesList = $this->getServicesList();
        $tariffsList  = $this->getTariffsList($periodList->first()->period);

        return compact('periodList', 'servicesList', 'tariffsList', 'sectorMain');
    }

    /**
     * Метод вызывается когда выбирают срок действия в списке тарифов.
     * Перерисовываются фрагменты с названиями тарифов, стоимость и действующие услуги с кнопками выбора.
     * @return array
     */
    public function onChangePeriod()
    {
        $sectorMain   = $this->getSectorMainList(); //Для приортетного размещения

        $cost = TariffCostPeriod::where('id', post('id'))->first();
        $tariffId = post('tariff_id');

        return ["#price-block-{$tariffId}" => $this->renderPartial('@tariff_cost_card', compact('cost'))];
    }

    /**
     * Метод вызывается при выборе в выпадающем списке "Категорий" для поиска "Подкатегорий" последнего уровня
     * @return array|string[]
     */
    public function onSelectSectorMain()
    {
        if (post('sector_main_id', 0) == 0) return ['#sectorByMain' => '', '#programBySectorLast' => ''];

        //Ищем все подкатегории последнего уровня, которые отвлевляются от категории первого уровня.
        $categoriesByFirstLevel = \DB::table('cds_study_inline_tree as it')->where('it.sector_id', post('sector_main_id'))
            ->join('cds_study_sectors as ss', function ($qSector) {
                $qSector->on('it.child', '=', 'ss.id')->where('ss.has_child', 0);
            })->select('ss.id', 'ss.name', 'ss.has_child')->lists('id');

        //Выбираем только те категории, которые привязаны к нашим программам обучения.
        $subCategoryLastLevel = Sector::whereIn('id', $categoriesByFirstLevel)->whereHas('programs', function ($qProg) {
            $qProg->whereHas('organization', function ($qOrg) {
                $qOrg->my();
            });
        })->select('id', 'name')->lists('name', 'id');

        return ['#sectorByMain' => $this->renderPartial('@select_sector_last', compact('subCategoryLastLevel'))];
    }

    /**
     * Метод вызывается при выборе в выпадающем списке "Подкатегорий" для поиска программ обучения
     * @return array|string[]
     */
    public function onSelectSectorLast()
    {
        if (post('sector_last_id', 0) == 0) return ['#programBySectorLast' => ''];

        $program = Program::where('sector_id', post('sector_last_id'))
            ->whereHas('organization', function ($qOrg) {
                $qOrg->my();
            })->select('id', 'name')->lists('name', 'id');

        return ['#programBySectorLast' => $this->renderPartial('@select_program', compact('program'))];
    }

    /**
     * Отрисовка модального окна для выбранного тарифного плана с дальнейшим выбором организации. (ДЛЯ ОРГАНИЗАЦИЙ)
     * @param $params
     * @return array
     */
    public function onRenderModalSelected($params)
    {
        $organizations = $this->getMyOrganization($params)->get();
        $tariff        = $this->getTariffWithCost($params);
        $organizationRequisites = BillProperty::where('user_id', Auth::id())->orderBy('created_at', 'desc')->first();

        return compact('tariff', 'organizations', 'organizationRequisites');
    }

    /**
     * Отрисовка модального окна для выбранного срока приоритетного размещения. (ДЛЯ ПРОГРАММ ОБУЧЕНИЯ)
     * @param $params
     * @return array
     */
    public function onRenderModalSelectedProgram($params)
    {
        $program = $this->getProgram(post('program_id'));
        $tariff  = $this->getTariffWithCost($params);

        return compact('tariff', 'program');
    }

    /**
     * Метод для выставления счета.
     * @return array
     */
    public function onMakeBill()
    {
        $userId = ['user_id' => Auth::id()];
        $organizationRequisites = BillProperty::create(post('requisites') + $userId);

        $data = post('props') + ['bill_property_id' => $organizationRequisites->id] + $userId;

        $tariff = $this->getTariffWithCost(array_only($data, ['tariff_id', 'cost_period']));
        $data['cost'] = $tariff->cost_period->cost;

        $dateNow = Carbon::now();

        //рассчитываем срок действия тарифа
        $data['date_start'] = Carbon::now();
        $data['date_end']   = Carbon::now()->addMonth($tariff->cost_period->period);

        //если нажали Включить по окончанию текущего тарифа, то ищем последний оплаченный тариф и формируем срок действия.
        if (empty($data['dateStart'])) {
            $bill = Bill::my()
                ->where('object_type', $this->classPath[$this->property('classObject')])
                ->where('object_id',   $data['object_id'])
                ->where('status', 1)
                ->whereDate('date_end', '>=', $dateNow)
                ->orderBy('date_end', 'desc')
                ->first();

            if (!empty($bill)) {
                $data['date_start'] = $bill->date_end->addDay(1);
                $data['date_end']   = $bill->date_end->addDay(1)->addMonth($tariff->cost_period->period);
            }
        }

        //формируем счет
        $billData = $this->getBillData($this->createBill($data)->id);

        //запрашиваем список всех текущих счетов для обновления фрагмента на странице
        $tariffsList = $this->getCurrentTariffsList();

        Flash::success("Счёт №{$billData->id} успешно сформирован");
        return [
            '#current_tariffs_list' => $this->renderPartial('@current_tariff_card', compact('tariffsList')),
        ];
    }

    /****************************************** Внутрение методы компонента. *****************************************/

    /**
     * Генеруем из шаблона(cds/study/views/pdf/bills) PDF файл, сохраняем и отдаём ссылку.
     * @param $data
     * @return string
     */
    private function getPdfBill($data)
    {
        $templateCode = 'cds.study::pdf.bills'; // unique code of the template
        $storagePath =  storage_path('app/uploads/public/');

        $pdf_file_name =  snake_case('№' . $data->id . '_' . $data->created_at->format('d.m.Y')) . '.pdf';
        $pdf_file_name_directory =  $storagePath . $pdf_file_name;

        PDF::loadTemplate($templateCode, ['data' => $data])->setPaper('a4', 'portrait')->save($pdf_file_name_directory);

        return $baseUrl = url(Config::get('cms.storage.uploads.path')) . '/public/' . $pdf_file_name;
    }

    /**
     * Получаем список текущих тарифных планов (не олпаченных, действующих)
     * @return mixed
     */
    private function getCurrentTariffsList()
    {
        $classObject = $this->property('classObject');
        $dateNow = Carbon::now();

        $bills = Bill::my()
            ->with(['tariff' => function ($q) {
                return $q;//->active();
            }])
            ->with(['object'])
            ->whereDate('date_end', '>=', $dateNow)
            ->where('object_type', $this->classPath[$classObject])
            ->orderBy('status', 'asc')->orderBy('date_end', 'desc')->get();
        return $bills;
    }

    private function getHistoryTariffsList()
    {
        $dateNow = Carbon::now();
        $bills = Bill::my()->whereDate('date_end', '<', $dateNow)
            ->where('object_type', $this->classPath[$this->property('classObject')])
            ->with(['tariff'])
            ->with(['object'])
            ->orderBy('updated_at', 'desc')
            ->get();
        return $bills;
    }

    /**
     * Выставляем счёт пользователю
     * @param $data
     * @return mixed
     */
    private function createBill($data)
    {
        $data += ['user_id' => Auth::id()];

        if ($this->property('classObject') == 'Organization') {
            return $this->getOrganization($data['object_id'])->bills()->create($data);
        } else {
            return $this->getProgram($data['object_id'])->bills()->create($data);
        }
    }

    /**
     * Получаем данные по выставленному счету
     * @param $bill_id
     * @return mixed
     */
    private function getBillData($bill_id)
    {
        return Bill::where('id', $bill_id)->with(['object', 'tariff', 'prop'])->first();
    }

    /**
     * Получаем список финансовых операций
     * @param array $date
     * @return mixed
     */
    private function getFinanceList($date = [])
    {
        $bills = Bill::my()->where('status', 1)->with('tariff')->orderBy('updated_at', 'asc');

        if (!empty($date['date_start'])) {
            $date['date_start'] = Carbon::createFromFormat('d.m.Y', $date['date_start']);
            $bills->whereDate('updated_at', '>=', $date['date_start']);
        } else {
            $bills->whereDate('updated_at', '>=', Carbon::now()->startOfMonth());
        }

        if (!empty($date['date_end'])) {
            $date['date_end'] = Carbon::createFromFormat('d.m.Y', $date['date_end']);
            $bills->whereDate('updated_at', '<=', $date['date_end']);
        } else {
            $bills->whereDate('updated_at', '<=', Carbon::now());
        }

        $bills = $bills->get();

        $bills->sumAll = Bill::getCostFormated($bills->sum('cost'));

        return $bills;
    }

    /**
     * Получаем "Категорию" первого уровня для "моих" программ обучения для страницы Приоритетное размещение
     * @return mixed
     */
    private function getSectorMainList()
    {
        //категории 1 уровня
        $sectorMain = Sector::getRootCategories()
            ->join('cds_study_inline_tree as it', 'cds_study_sectors.id', 'it.sector_id')
            ->join('cds_study_programs as p', function ($qProg) {
                $qProg->on('p.sector_id', '=', 'it.child')
                    ->whereIn('p.organization_id', Organization::my()->lists('id'));
            })->select('cds_study_sectors.id', 'cds_study_sectors.name')->lists('name', 'id');

        return $sectorMain;
    }

    /**
     * Получаем данные по тарифу и стоимости по ID тарифа и ID стоимости
     * @param $param
     * @return mixed
     */
    private function getTariffWithCost($param)
    {
        $tariffWithCost = Tariff::where('id', $param['tariff_id'])->with(['costs_periods' => function($qCost) use($param) {
            return $qCost->where('id', $param['cost_period']);
        }])->first();

        $tariffWithCost->cost_period = $tariffWithCost->costs_periods->first();

        return $tariffWithCost;
    }

    /**
     * Получаем список всех тарифных планов для страницы Тарифы или Приоритетное размещение
     * @return mixed
     */
    private function getTariffCostPeriodList()
    {
        $classObject = $this->property('classObject');

        $periodList = TariffCostPeriod::whereHas('tariff', function ($q) {
            $q->where('active', true);
        });

        //Для организаций выводим все тарифы кроме приоритетного размещение, для программ наоборот
        if ($classObject == 'Organization') {
            $period = $periodList->where('tariff_id', '<>', '4');
        } else {
            $period = $periodList->where('tariff_id', '4');
        }

        $periodList = $periodList->orderBy('period', 'asc')->select('period')->get()->unique('period');

        return $periodList;
    }

    /**
     * Получаем список всех "моих" организаций
     * @return mixed
     */
    private function getMyOrganization($params)
    {
        $query = Organization::my();

        if ($id = $params['tariff_id'] == 1)
            return $query->whereDoesntHave('bills', function ($q) use($id) {
                $q->where('tariff_id', $id);
            });

        return $query;
    }

    /**
     * Получаем данные по конкретной организации по ID
     * @param $id
     * @return bool
     */
    private function getOrganization($id)
    {
        if (empty($id)) return false;

        return Organization::where('id', $id)->first();
    }

    public function onGetOrganization()
    {
        $id = post('org_id');

        $org = $this->getOrganization($id);
        $logo = $org->getPublicLogo();

        return ['org' => $org, 'logo' => $logo];
    }

    /**
     * Получаем данные по конкретной программе по ID
     * @param $id
     * @return bool
     */
    private function getProgram($id)
    {
        if (empty($id)) return false;

        return Program::where('id', $id)->with(['sector', 'organization'])->first();
    }

    /**
     * Функция определяет для какого объекта Организация или Программа нужно получить данные, для выставления счета
     * @param array $props
     * @return bool|null
     */
    private function getObjectData($props = [])
    {
        if ($this->property('classObject') == 'Organization') {
            return $this->getOrganization($props['organization_id']);
        }

        if ($this->property('classObject') == 'Program') {
            return $this->getProgram($props['program_id']);
        }

        return null;
    }

    /**
     * Получаем список услуг для странц Тарифов и Приоритетного размещения
     * @return mixed
     */
    private function getServicesList()
    {
        $classObject = $this->property('classObject');

        return Service::active()->where('class', $classObject)->orderBy('id', 'asc')
            ->select('id', 'title')->lists('title', 'id');
    }

    /**
     * Получаем список тарифных планов для страниц Тарифов и Приоритеного размещения
     * @param int $period
     * @return mixed
     */
    private function getTariffsList($period = 1)
    {
        $classObject = $this->property('classObject');

        return Tariff::active()
            ->whereHas('services', function ($q) use ($classObject) {
                $q->where('class', $classObject);
            })
            ->with(['services'])
            ->with(['costs_periods' => function ($q) use ($period) {
                return $q->orderBy('period', 'asc');
            }])
            ->orderBy('id', 'asc')
            ->get();
    }
}
