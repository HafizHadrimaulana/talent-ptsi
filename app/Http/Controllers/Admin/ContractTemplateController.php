<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContractTemplate;
use Illuminate\Http\Request;

class ContractTemplateController extends Controller
{
    public function index()
    {
        $templates = ContractTemplate::orderBy('name')->get();
        return view('admin.contract_templates.index', compact('templates'));
    }

    public function create()
    {
        return view('admin.contract_templates.form', ['template' => new ContractTemplate()]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|unique:contract_templates,code',
            'name' => 'required',
            'body' => 'required',
        ]);

        ContractTemplate::create($request->all());
        return redirect()->route('admin.contract-templates.index')->with('success', 'Template berhasil dibuat');
    }

    public function edit($id)
    {
        $template = ContractTemplate::findOrFail($id);
        return view('admin.contract_templates.form', compact('template'));
    }

    public function update(Request $request, $id)
    {
        $template = ContractTemplate::findOrFail($id);
        $request->validate([
            'code' => 'required|unique:contract_templates,code,'.$id,
            'name' => 'required',
            'body' => 'required',
        ]);

        $template->update($request->all());
        return redirect()->route('admin.contract-templates.index')->with('success', 'Template berhasil diperbarui');
    }

    public function destroy($id)
    {
        ContractTemplate::findOrFail($id)->delete();
        return redirect()->route('admin.contract-templates.index')->with('success', 'Template dihapus');
    }
}