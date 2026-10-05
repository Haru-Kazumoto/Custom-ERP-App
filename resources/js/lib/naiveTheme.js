/**
 * Naive UI Global Theme — SaaS Blue Sky
 *
 * Centralized themeOverrides untuk NConfigProvider.
 * - Primary hijau default (#18a058) diganti Blue Sky (#0284c7 / #0ea5e9)
 * - Radius SaaS-friendly: 10px global, 12px card/modal (tidak tajam)
 * - Sinkron dengan tailwind.config.js primary: 500 #0ea5e9, 600 #0284c7, 700 #0369a1

 * Pakai di `resources/js/Layouts/AppLayout.vue:58`:
 *   <NConfigProvider :theme-overrides="naiveThemeOverrides">
 *
 * @type {import('naive-ui').GlobalThemeOverrides}
 */
export const naiveThemeOverrides = {
    // ──────────────────────────────────────────────
    // Component overrides - SaaS polish
    // ──────────────────────────────────────────────
    Button: {
        // semua ukuran pakai radius SaaS (default 3px tajam -> 10px/12px)
        borderRadiusTiny: '8px',
        borderRadiusSmall: '10px',
        borderRadiusMedium: '10px',
        borderRadiusLarge: '12px',
        fontWeightStrong: '600',
        // tinggi slightly lebih lega untuk SaaS (default 34px -> 36px untuk medium)
        // heightMedium: '36px', // uncomment jika ingin tombol lebih tinggi
    },

    Card: {
        borderRadius: '12px',
        // borderColor sudah dari common.dividerColor, tapi bisa explicit
        // boxShadow lebih soft untuk card SaaS
        boxShadow: '0 1px 3px 0 rgba(0,0,0,0.06), 0 1px 2px -1px rgba(0,0,0,0.06)',
        // text & title colors ikut common
    },

    Input: {
        borderRadius: '10px',
        // borderFocus & boxShadowFocus auto derived dari primaryColor, jadi sudah blue
        // pastikan height konsisten SaaS
        // heightMedium: '36px',
    },

    InputNumber: {
        borderRadius: '10px',
    },

    Dialog: {
        borderRadius: '12px',
    },

    Modal: {
        // header card di NModal preset="card" ikut Card token, tapi modal box juga
        // naive modal memakai Card peer; radius 12px global card sudah cover
    },

    Tag: {
        borderRadius: '8px',
    },

    Checkbox: {
        borderRadius: '6px',
    },

    Radio: {
        // radio dot tetap bulat; box shadow focus ikut primary
    },

    Tabs: {
        // tab SaaS: barColor blue?
        // tabTextColorActive: '#0284c7', // auto dari primary
    },

    // ── Peers: Select / AutoComplete / Cascader ──
    Select: {
        peers: {
            InternalSelection: {
                borderRadius: '10px',
                // boxShadowFocus & borderHover auto dari primaryColor (blue)
                // explicit jika ingin lebih soft:
                // border: '1px solid #e2e8f0',
                // borderHover: '1px solid #0ea5e9',
                // borderActive: '1px solid #0284c7',
                // boxShadowFocus: '0 0 0 2px rgba(14,165,233,0.2)',
            },
            InternalSelectMenu: {
                borderRadius: '10px',
            },
        },
    },

    AutoComplete: {
        peers: {
            InternalSelection: {
                borderRadius: '10px',
            },
            InternalSelectMenu: {
                borderRadius: '10px',
            },
        },
    },

    Cascader: {
        peers: {
            InternalSelection: {
                borderRadius: '10px',
            },
            InternalSelectMenu: {
                borderRadius: '10px',
            },
        },
    },

    TreeSelect: {
        peers: {
            InternalSelection: {
                borderRadius: '10px',
            },
            InternalSelectMenu: {
                borderRadius: '10px',
            },
        },
    },

    DatePicker: {
        peers: {
            Input: {
                borderRadius: '10px',
            },
            Button: {
                borderRadiusMedium: '10px',
            },
        },
        panelBorderRadius: '12px',
        calendarTitleFontWeight: '600',
    },

    TimePicker: {
        // ikut DatePicker/Input
    },

    DataTable: {
        borderRadius: '12px',
        // thColor sudah #f8fafc dari common.tableHeaderColor
        thFontWeight: '600',
    },

    Table: {
        borderRadius: '12px',
    },

    Pagination: {
        // button radius ikut Button peer
        // itemBorderRadius: '8px' // jika ingin pagination pill lebih round
    },

    Dropdown: {
        borderRadius: '10px',
    },

    Popover: {
        borderRadius: '10px',
    },

    Popselect: {
        peers: {
            InternalSelection: {
                borderRadius: '10px',
            },
        },
    },

    Tooltip: {
        borderRadius: '8px',
    },

    Notification: {
        borderRadius: '12px',
    },

    Message: {
        borderRadius: '10px',
    },

    Steps: {
        // Steps indicator sudah pakai primaryColor (#0284c7)
        // ApproveTimeline.vue sudah ada local override, global ini sebagai fallback
    },

    Form: {
        feedbackFontSizeSmall: '12px',
        feedbackFontSizeMedium: '12px',
    },

    Upload: {
        // dragger radius SaaS
        // draggerBorderRadius: '12px'
    },

    // Scrollbar tidak perlu override khusus
};

/**
 * Optional: untuk createDiscreteApi (message/notification/dialog)
 * Jika suatu saat pakai `createDiscreteApi({ themeOverrides })`
 */
export const naiveDiscreteThemeOverrides = naiveThemeOverrides;

export default naiveThemeOverrides;
