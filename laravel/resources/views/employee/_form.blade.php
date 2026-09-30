{{-- Fields shared by employee create/edit. $emp is null when creating. --}}
@php $emp ??= null; @endphp

@if ($emp)
    <x-form-field id="EmpPersonalCode" label="Employee Code" :value="$emp->EmpPersonalCode" readonly :required="false" />
@else
    <x-form-field name="EmpPersonalCode" label="Employee Code" />
@endif

<x-form-field name="EmpNameInNepali" label="Name in Nepali" inputClass="form-control nepali" lang="ne" :value="$emp?->EmpNameInNepali" />

<x-form-field name="EmpName" label="Name in English" :value="$emp?->EmpName" />

<x-select-field name="Designation" id="designation" label="Designation" inputClass="form-control"
                :options="$designations->pluck('designationType', 'designationType')->all()"
                :selected="$emp?->Designation"
                :placeholder="$emp ? null : '-- Select Designation --'" />

<x-select-field name="LevelName" id="level" label="Level" inputClass="form-control"
                :options="$levels->pluck('levelName', 'levelName')->all()"
                :selected="$emp?->LevelName"
                :placeholder="$emp ? null : '-- Select Level --'" />

<x-select-field name="Gender" label="Gender"
                :options="['Male' => 'Male', 'Female' => 'Female', 'Other' => 'Other']"
                :selected="$emp?->Gender"
                :placeholder="$emp ? null : '-- Select Gender --'" />

<x-form-field name="Email" type="email" label="Email" :value="$emp?->Email" />
