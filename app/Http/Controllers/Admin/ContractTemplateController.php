<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContractTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
        $data = $request->validate([
            'code' => 'required|unique:contract_templates,code',
            'name' => 'required',
            'body' => 'required',
            'css'  => 'nullable',
            'header_image' => 'nullable|image|max:2048' // Max 2MB
        ]);

        if ($request->hasFile('header_image')) {
            $data['header_image_path'] = $request->file('header_image')->store('templates', 'public');
        }

        ContractTemplate::create($data);
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
        $data = $request->validate([
            'code' => 'required|unique:contract_templates,code,'.$id,
            'name' => 'required',
            'body' => 'required',
            'css'  => 'nullable',
            'header_image' => 'nullable|image|max:2048'
        ]);

        if ($request->hasFile('header_image')) {
            // Hapus file lama jika ada
            if ($template->header_image_path) {
                Storage::disk('public')->delete($template->header_image_path);
            }
            $data['header_image_path'] = $request->file('header_image')->store('templates', 'public');
        }

        $template->update($data);
        return redirect()->route('admin.contract-templates.index')->with('success', 'Template berhasil diperbarui');
    }

    public function destroy($id)
    {
        $template = ContractTemplate::findOrFail($id);
        if ($template->header_image_path) {
            Storage::disk('public')->delete($template->header_image_path);
        }
        $template->delete();
        return redirect()->route('admin.contract-templates.index')->with('success', 'Template dihapus');
    }
}