<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/** Filter dropdowns and the main query of the Domestic TADA report. */
class DomesticTadaReportService
{
    public function fiscalYears(): array
    {
        return DB::table('fiscal_year_master')->orderBy('fy', 'desc')->get(['fiscal_year_master_id', 'fy'])->all();
    }

    public function employees(): array
    {
        return DB::table('Employee_Information as e')
            ->join('DomesticTada as dt', 'e.EmpPersonalCode', '=', 'dt.EmpPersonalCode')
            ->select('e.EmpPersonalCode', 'e.EmpName')
            ->distinct()
            ->orderBy('e.EmpName')
            ->get()->all();
    }

    public function districts(): array
    {
        return DB::table('DistrictMaster as dm')
            ->join('DomesticTada as dt', 'dm.District_id', '=', 'dt.District_id')
            ->select('dm.District_id', 'dm.District_name', 'dm.District_name_nepali')
            ->distinct()
            ->orderBy('dm.District_name')
            ->get()->all();
    }

    public function designations(): array
    {
        return DB::table('Employee_Information as e')
            ->join('DomesticTada as dt', 'e.EmpPersonalCode', '=', 'dt.EmpPersonalCode')
            ->whereNotNull('e.Designation')
            ->select('e.Designation')
            ->distinct()
            ->orderBy('e.Designation')
            ->get()->all();
    }

    /** Legacy filter semantics: an empty value (including "0") means "no filter". */
    public function records(string $fy, string $emp, string $district, string $desig): array
    {
        $query = DB::table('DomesticTada as dt')
            ->leftJoin('Employee_Information as e', 'dt.EmpPersonalCode', '=', 'e.EmpPersonalCode')
            ->leftJoin('DistrictMaster as dm', 'dt.District_id', '=', 'dm.District_id')
            ->leftJoin('DomesticTadaDefinerMasterBylevel as dtd', 'e.LevelName', '=', 'dtd.DomesticTadaDefinerMasterBylevel_name')
            ->leftJoin('DesignationTypeMaster as dtm', 'e.Designation', '=', 'dtm.designationType')
            ->leftJoin('TraveltypeMaster as ttm', 'dt.TadaTypeMaster_id', '=', 'ttm.TadaTypeMaster_id')
            ->leftJoin('Users as u', 'dt.domestic_createdBy', '=', 'u.user_id')
            ->leftJoin('fiscal_year_master as fy', 'dt.fiscal_year_master_id', '=', 'fy.fiscal_year_master_id')
            ->select(
                'dt.domestic_tada_id', 'dt.domestic_Batch_id', 'dt.domestic_Chalani_id',
                'dt.domestic_form_date', 'dt.EmpPersonalCode',
                'e.EmpName', 'e.EmpNameInNepali', 'e.Designation', 'e.LevelName',
                'dtm.designationTypeInNepali',
                'dt.District_id', 'dm.District_name', 'dm.District_name_nepali',
                'dt.domestic_travel_objective',
                'dt.domestic_travelDateStart', 'dt.domestic_travelDateEnd',
                'dt.domestic_totalday', 'dt.domestic_tada',
                'dt.domestic_isTwentyPercentExtra', 'dtd.tadaInNepali',
                'ttm.type as travel_type',
                'fy.fy as fiscal_year',
                'u.username as created_by'
            );

        if (! empty($fy)) {
            $query->where('dt.fiscal_year_master_id', $fy);
        }
        if (! empty($emp)) {
            $query->where('dt.EmpPersonalCode', $emp);
        }
        if (! empty($district)) {
            $query->where('dt.District_id', $district);
        }
        if (! empty($desig)) {
            $query->where('e.Designation', $desig);
        }

        return $query->orderBy('dt.domestic_tada_id', 'desc')->get()->all();
    }
}
