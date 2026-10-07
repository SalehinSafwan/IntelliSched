<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CRCommunicationController extends Controller
{
    /**
     * Display the CR & ACR communication portal for teachers.
     */
    public function index(Request $request)
    {
        $selectedBatch = $request->query('batch', 'all');
        $selectedSection = $request->query('section', 'all');
        $searchQuery = strtolower(trim($request->query('search', '')));

        // Batches structure: 2k21, 2k22, 2k23, 2k24, 2k25
        // Every batch has 2 CRs and 2 ACRs (1 CR & 1 ACR per Section A and Section B)
        $representatives = [
            '2k21' => [
                'batch' => '2k21',
                'name'  => 'Batch 2k21 (8th Semester)',
                'sections' => [
                    'Section A' => [
                        [
                            'id' => 'REP-21A-1',
                            'role' => 'CR',
                            'role_title' => 'Class Representative',
                            'name' => 'Md. Tanvir Hasan',
                            'student_id' => '2021-1-60-012',
                            'email' => 'tanvir.2k21.a@intellisched.edu',
                            'phone' => '+880 1712-345678',
                            'batch' => '2k21',
                            'section' => 'Section A',
                            'avatar_bg' => 'linear-gradient(135deg, #6366f1, #8b5cf6)',
                            'status' => 'Active',
                        ],
                        [
                            'id' => 'REP-21A-2',
                            'role' => 'ACR',
                            'role_title' => 'Assistant CR',
                            'name' => 'Nusrat Jahan',
                            'student_id' => '2021-1-60-019',
                            'email' => 'nusrat.2k21.a@intellisched.edu',
                            'phone' => '+880 1819-234567',
                            'batch' => '2k21',
                            'section' => 'Section A',
                            'avatar_bg' => 'linear-gradient(135deg, #ec4899, #8b5cf6)',
                            'status' => 'Active',
                        ],
                    ],
                    'Section B' => [
                        [
                            'id' => 'REP-21B-1',
                            'role' => 'CR',
                            'role_title' => 'Class Representative',
                            'name' => 'Sabbir Ahmed',
                            'student_id' => '2021-1-60-045',
                            'email' => 'sabbir.2k21.b@intellisched.edu',
                            'phone' => '+880 1911-987654',
                            'batch' => '2k21',
                            'section' => 'Section B',
                            'avatar_bg' => 'linear-gradient(135deg, #10b981, #059669)',
                            'status' => 'Active',
                        ],
                        [
                            'id' => 'REP-21B-2',
                            'role' => 'ACR',
                            'role_title' => 'Assistant CR',
                            'name' => 'Farhan Ishrak',
                            'student_id' => '2021-1-60-058',
                            'email' => 'farhan.2k21.b@intellisched.edu',
                            'phone' => '+880 1675-432109',
                            'batch' => '2k21',
                            'section' => 'Section B',
                            'avatar_bg' => 'linear-gradient(135deg, #f59e0b, #d97706)',
                            'status' => 'Active',
                        ],
                    ],
                ]
            ],
            '2k22' => [
                'batch' => '2k22',
                'name'  => 'Batch 2k22 (6th Semester)',
                'sections' => [
                    'Section A' => [
                        [
                            'id' => 'REP-22A-1',
                            'role' => 'CR',
                            'role_title' => 'Class Representative',
                            'name' => 'Rafid Islam',
                            'student_id' => '2022-1-60-008',
                            'email' => 'rafid.2k22.a@intellisched.edu',
                            'phone' => '+880 1715-112233',
                            'batch' => '2k22',
                            'section' => 'Section A',
                            'avatar_bg' => 'linear-gradient(135deg, #6366f1, #3b82f6)',
                            'status' => 'Active',
                        ],
                        [
                            'id' => 'REP-22A-2',
                            'role' => 'ACR',
                            'role_title' => 'Assistant CR',
                            'name' => 'Anika Tabassum',
                            'student_id' => '2022-1-60-024',
                            'email' => 'anika.2k22.a@intellisched.edu',
                            'phone' => '+880 1822-334455',
                            'batch' => '2k22',
                            'section' => 'Section A',
                            'avatar_bg' => 'linear-gradient(135deg, #a855f7, #ec4899)',
                            'status' => 'Active',
                        ],
                    ],
                    'Section B' => [
                        [
                            'id' => 'REP-22B-1',
                            'role' => 'CR',
                            'role_title' => 'Class Representative',
                            'name' => 'Mehedi Hasan',
                            'student_id' => '2022-1-60-041',
                            'email' => 'mehedi.2k22.b@intellisched.edu',
                            'phone' => '+880 1933-445566',
                            'batch' => '2k22',
                            'section' => 'Section B',
                            'avatar_bg' => 'linear-gradient(135deg, #06b6d4, #3b82f6)',
                            'status' => 'Active',
                        ],
                        [
                            'id' => 'REP-22B-2',
                            'role' => 'ACR',
                            'role_title' => 'Assistant CR',
                            'name' => 'Sumaiya Rahman',
                            'student_id' => '2022-1-60-062',
                            'email' => 'sumaiya.2k22.b@intellisched.edu',
                            'phone' => '+880 1644-556677',
                            'batch' => '2k22',
                            'section' => 'Section B',
                            'avatar_bg' => 'linear-gradient(135deg, #10b981, #06b6d4)',
                            'status' => 'Active',
                        ],
                    ],
                ]
            ],
            '2k23' => [
                'batch' => '2k23',
                'name'  => 'Batch 2k23 (4th Semester)',
                'sections' => [
                    'Section A' => [
                        [
                            'id' => 'REP-23A-1',
                            'role' => 'CR',
                            'role_title' => 'Class Representative',
                            'name' => 'Asif Iqbal',
                            'student_id' => '2023-1-60-015',
                            'email' => 'asif.2k23.a@intellisched.edu',
                            'phone' => '+880 1755-667788',
                            'batch' => '2k23',
                            'section' => 'Section A',
                            'avatar_bg' => 'linear-gradient(135deg, #8b5cf6, #6366f1)',
                            'status' => 'Active',
                        ],
                        [
                            'id' => 'REP-23A-2',
                            'role' => 'ACR',
                            'role_title' => 'Assistant CR',
                            'name' => 'Fariha Ahmed',
                            'student_id' => '2023-1-60-031',
                            'email' => 'fariha.2k23.a@intellisched.edu',
                            'phone' => '+880 1866-778899',
                            'batch' => '2k23',
                            'section' => 'Section A',
                            'avatar_bg' => 'linear-gradient(135deg, #ec4899, #f43f5e)',
                            'status' => 'Active',
                        ],
                    ],
                    'Section B' => [
                        [
                            'id' => 'REP-23B-1',
                            'role' => 'CR',
                            'role_title' => 'Class Representative',
                            'name' => 'Kazi Nabil',
                            'student_id' => '2023-1-60-052',
                            'email' => 'nabil.2k23.b@intellisched.edu',
                            'phone' => '+880 1977-889900',
                            'batch' => '2k23',
                            'section' => 'Section B',
                            'avatar_bg' => 'linear-gradient(135deg, #3b82f6, #1d4ed8)',
                            'status' => 'Active',
                        ],
                        [
                            'id' => 'REP-23B-2',
                            'role' => 'ACR',
                            'role_title' => 'Assistant CR',
                            'name' => 'Mahia Chowdhury',
                            'student_id' => '2023-1-60-070',
                            'email' => 'mahia.2k23.b@intellisched.edu',
                            'phone' => '+880 1688-990011',
                            'batch' => '2k23',
                            'section' => 'Section B',
                            'avatar_bg' => 'linear-gradient(135deg, #f59e0b, #ef4444)',
                            'status' => 'Active',
                        ],
                    ],
                ]
            ],
            '2k24' => [
                'batch' => '2k24',
                'name'  => 'Batch 2k24 (2nd Semester)',
                'sections' => [
                    'Section A' => [
                        [
                            'id' => 'REP-24A-1',
                            'role' => 'CR',
                            'role_title' => 'Class Representative',
                            'name' => 'Saimon Mahmud',
                            'student_id' => '2024-1-60-003',
                            'email' => 'saimon.2k24.a@intellisched.edu',
                            'phone' => '+880 1799-001122',
                            'batch' => '2k24',
                            'section' => 'Section A',
                            'avatar_bg' => 'linear-gradient(135deg, #10b981, #047857)',
                            'status' => 'Active',
                        ],
                        [
                            'id' => 'REP-24A-2',
                            'role' => 'ACR',
                            'role_title' => 'Assistant CR',
                            'name' => 'Tasnim Kabir',
                            'student_id' => '2024-1-60-022',
                            'email' => 'tasnim.2k24.a@intellisched.edu',
                            'phone' => '+880 1800-112233',
                            'batch' => '2k24',
                            'section' => 'Section A',
                            'avatar_bg' => 'linear-gradient(135deg, #8b5cf6, #d946ef)',
                            'status' => 'Active',
                        ],
                    ],
                    'Section B' => [
                        [
                            'id' => 'REP-24B-1',
                            'role' => 'CR',
                            'role_title' => 'Class Representative',
                            'name' => 'Rayhan Ahmed',
                            'student_id' => '2024-1-60-048',
                            'email' => 'rayhan.2k24.b@intellisched.edu',
                            'phone' => '+880 1911-223344',
                            'batch' => '2k24',
                            'section' => 'Section B',
                            'avatar_bg' => 'linear-gradient(135deg, #6366f1, #4338ca)',
                            'status' => 'Active',
                        ],
                        [
                            'id' => 'REP-24B-2',
                            'role' => 'ACR',
                            'role_title' => 'Assistant CR',
                            'name' => 'Jannatul Ferdous',
                            'student_id' => '2024-1-60-065',
                            'email' => 'jannat.2k24.b@intellisched.edu',
                            'phone' => '+880 1622-334455',
                            'batch' => '2k24',
                            'section' => 'Section B',
                            'avatar_bg' => 'linear-gradient(135deg, #06b6d4, #0891b2)',
                            'status' => 'Active',
                        ],
                    ],
                ]
            ],
            '2k25' => [
                'batch' => '2k25',
                'name'  => 'Batch 2k25 (1st Semester)',
                'sections' => [
                    'Section A' => [
                        [
                            'id' => 'REP-25A-1',
                            'role' => 'CR',
                            'role_title' => 'Class Representative',
                            'name' => 'Tahmid Hasan',
                            'student_id' => '2025-1-60-005',
                            'email' => 'tahmid.2k25.a@intellisched.edu',
                            'phone' => '+880 1733-445566',
                            'batch' => '2k25',
                            'section' => 'Section A',
                            'avatar_bg' => 'linear-gradient(135deg, #f59e0b, #b45309)',
                            'status' => 'Active',
                        ],
                        [
                            'id' => 'REP-25A-2',
                            'role' => 'ACR',
                            'role_title' => 'Assistant CR',
                            'name' => 'Sadia Sultana',
                            'student_id' => '2025-1-60-018',
                            'email' => 'sadia.2k25.a@intellisched.edu',
                            'phone' => '+880 1844-556677',
                            'batch' => '2k25',
                            'section' => 'Section A',
                            'avatar_bg' => 'linear-gradient(135deg, #ec4899, #be185d)',
                            'status' => 'Active',
                        ],
                    ],
                    'Section B' => [
                        [
                            'id' => 'REP-25B-1',
                            'role' => 'CR',
                            'role_title' => 'Class Representative',
                            'name' => 'Zaid Hossain',
                            'student_id' => '2025-1-60-039',
                            'email' => 'zaid.2k25.b@intellisched.edu',
                            'phone' => '+880 1955-667788',
                            'batch' => '2k25',
                            'section' => 'Section B',
                            'avatar_bg' => 'linear-gradient(135deg, #6366f1, #8b5cf6)',
                            'status' => 'Active',
                        ],
                        [
                            'id' => 'REP-25B-2',
                            'role' => 'ACR',
                            'role_title' => 'Assistant CR',
                            'name' => 'Nabila Islam',
                            'student_id' => '2025-1-60-054',
                            'email' => 'nabila.2k25.b@intellisched.edu',
                            'phone' => '+880 1666-778899',
                            'batch' => '2k25',
                            'section' => 'Section B',
                            'avatar_bg' => 'linear-gradient(135deg, #10b981, #059669)',
                            'status' => 'Active',
                        ],
                    ],
                ]
            ],
        ];

        // Filter by batch if specified
        $filteredReps = $representatives;
        if ($selectedBatch !== 'all' && isset($representatives[$selectedBatch])) {
            $filteredReps = [
                $selectedBatch => $representatives[$selectedBatch]
            ];
        }

        // Recent broadcast communication logs
        $communicationLogs = [
            [
                'id' => 'MSG-101',
                'teacher' => 'Dr. Ahmed Rahman',
                'target_batch' => 'Batch 2k21',
                'target_section' => 'Section A & B',
                'target_roles' => 'CRs & ACRs (4 Reps)',
                'subject' => 'CSE3201 Lab Report #3 Submission Extended',
                'category' => 'Assignment Notice',
                'sent_at' => 'Today at 09:30 AM',
                'status' => 'Delivered (4/4 Read)',
                'badge' => 'bg-success',
            ],
            [
                'id' => 'MSG-102',
                'teacher' => 'Dr. Ahmed Rahman',
                'target_batch' => 'Batch 2k22',
                'target_section' => 'Section A',
                'target_roles' => 'CR & ACR',
                'subject' => 'Class Test 2 Syllabus & Room Allocation',
                'category' => 'Exam Announcement',
                'sent_at' => 'Yesterday at 04:15 PM',
                'status' => 'Delivered (2/2 Read)',
                'badge' => 'bg-info',
            ],
            [
                'id' => 'MSG-103',
                'teacher' => 'Dr. Ahmed Rahman',
                'target_batch' => 'Batch 2k23',
                'target_section' => 'Section B',
                'target_roles' => 'CR & ACR',
                'subject' => 'Makeup Class Rescheduled for Thursday 2:00 PM',
                'category' => 'Class Schedule',
                'sent_at' => '05 Oct 2026',
                'status' => 'Delivered (2/2 Read)',
                'badge' => 'bg-indigo',
            ],
        ];

        return view('teachers.cr_communication', compact(
            'representatives',
            'filteredReps',
            'selectedBatch',
            'selectedSection',
            'searchQuery',
            'communicationLogs'
        ));
    }

    /**
     * Handle sending broadcast notice or direct message to CRs/ACRs.
     */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'target_batch' => 'required',
            'subject'      => 'required|string|max:255',
            'message_body' => 'required|string',
        ]);

        $batch = $request->input('target_batch');
        $section = $request->input('target_section', 'All Sections');

        return redirect()->route('teacher.cr-communication', ['batch' => $batch])
            ->with('success', "Notice broadcasted successfully to $batch ($section) CRs and ACRs via Portal Notifications & Email!");
    }
}
