<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .intl-form-page { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; min-height: 100vh; }
    .intl-form-page .form-container { max-width: 1100px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
    .intl-form-page h2 { color: #1E3A8A; margin-bottom: 25px; text-align: center; font-size: 28px; }
    .intl-form-page .info-badges { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 25px; }
    .intl-form-page .info-badges .info-badge { background: linear-gradient(135deg, #E0E7FF 0%, #C7D2FE 100%); color: #3730A3; padding: 12px 15px; border-radius: 8px; font-weight: 600; text-align: center; border: 2px solid #A5B4FC; }
    .intl-form-page .info-badge.edit-badge { background: linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%); color: #92400E; padding: 12px 15px; border-radius: 8px; font-weight: 600; text-align: center; border: 2px solid #FCD34D; margin-bottom: 25px; }
    .intl-form-page .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
    .intl-form-page .form-group { margin-bottom: 20px; }
    .intl-form-page .form-group.full-width { grid-column: 1 / -1; }
    .intl-form-page label { display: block; margin-bottom: 8px; color: #1F2937; font-weight: 600; font-size: 14px; }
    .intl-form-page label .required { color: #EF4444; }
    .intl-form-page input[type="text"], .intl-form-page input[type="date"], .intl-form-page input[type="number"], .intl-form-page select, .intl-form-page textarea { width: 100%; padding: 10px 12px; border: 2px solid #e0e0e0; border-radius: 6px; font-size: 14px; transition: border-color 0.3s, box-shadow 0.3s; box-sizing: border-box; }
    .intl-form-page input:focus, .intl-form-page select:focus, .intl-form-page textarea:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
    .intl-form-page textarea { resize: vertical; min-height: 90px; font-family: inherit; }
    .select2-container--default .select2-selection--single { border: 2px solid #e0e0e0; border-radius: 6px; height: 44px; }
    .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 40px; padding-left: 12px; }
    .select2-container--default .select2-selection--single .select2-selection__arrow { height: 40px; }
    .select2-container--default.select2-container--focus .select2-selection--single { border-color: #667eea; }
    .select2-container { width: 100% !important; }
    .intl-form-page .employee-section { background: #F9FAFB; padding: 20px; border-radius: 8px; margin: 20px 0; border: 2px dashed #D1D5DB; }
    .intl-form-page .employee-selector { display: flex; gap: 10px; margin-bottom: 15px; align-items: stretch; }
    .intl-form-page .employee-selector select { flex: 1; }
    .intl-form-page .btn-add-employee { background: #10B981; color: white; padding: 10px 24px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; white-space: nowrap; transition: background 0.3s, transform 0.1s; }
    .intl-form-page .btn-add-employee:hover { background: #059669; transform: translateY(-1px); }
    .intl-form-page .btn-add-employee:active { transform: translateY(0); }
    .intl-form-page .employee-table-container { overflow-x: auto; margin-top: 15px; border-radius: 8px; border: 1px solid #E5E7EB; }
    .intl-form-page .employee-table { width: 100%; border-collapse: collapse; background: white; }
    .intl-form-page .employee-table thead { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
    .intl-form-page .employee-table th { padding: 14px 12px; text-align: left; font-weight: 600; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; background: transparent; color: white; }
    .intl-form-page .employee-table td { padding: 14px 12px; border-bottom: 1px solid #E5E7EB; font-size: 14px; }
    .intl-form-page .employee-table tbody tr:hover { background: #F3F4F6; }
    .intl-form-page .employee-table tbody tr:last-child td { border-bottom: none; }
    .intl-form-page .btn-remove { background: #EF4444; color: white; border: none; padding: 6px 14px; border-radius: 5px; cursor: pointer; font-size: 12px; font-weight: 600; transition: background 0.3s; }
    .intl-form-page .btn-remove:hover { background: #DC2626; }
    .intl-form-page .empty-state { text-align: center; padding: 40px 20px; color: #6B7280; }
    .intl-form-page .empty-state-icon { font-size: 48px; margin-bottom: 10px; }
    .intl-form-page .btn { padding: 12px 24px; border: none; border-radius: 6px; cursor: pointer; font-size: 15px; font-weight: 600; transition: all 0.3s; }
    .intl-form-page .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; width: 100%; padding: 14px; font-size: 16px; }
    .intl-form-page .btn-primary:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4); }
    .intl-form-page .btn-primary:disabled { background: #9CA3AF; cursor: not-allowed; transform: none; opacity: 0.6; }
    .intl-form-page .btn-secondary { background-color: #6B7280; color: white; }
    .intl-form-page .btn-secondary:hover { background-color: #4B5563; }
    .intl-form-page .loading { display: inline-block; margin-left: 10px; color: #667eea; animation: pulse 1.5s ease-in-out infinite; }
    @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
    .intl-form-page .form-actions { margin-top: 30px; display: flex; gap: 12px; }
    .intl-form-page .form-actions .btn-secondary { flex: 0 0 120px; }
    .intl-form-page .form-actions .btn-primary { flex: 1; }
</style>
