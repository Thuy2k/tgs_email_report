# Luồng sản phẩm global trong tgs_email_report

Plugin `tgs_email_report` chỉ tạo dữ liệu báo cáo email. Từ luồng global mới, mọi thông tin catalog sản phẩm như tên, SKU, giá sau thuế phải lấy qua `TGS_Global_Product_Source` của `tgs_shop_management`, thông qua adapter nội bộ `TGS_Email_Global_Products`.

## Nguồn dữ liệu

- Catalog sản phẩm: `TGS_Global_Product_Source` / bảng `wp_global_product_name`.
- Dữ liệu giao dịch: `local_ledger`, `local_ledger_item` của từng blog.
- Dữ liệu rollup: `tgs_fact_sales_daily`, `tgs_fact_inventory_daily`, `tgs_dim_product`.
- Mapping HCL theo SKU: `wp_global_product_sci_mapping`.
- Không đọc bảng catalog local như `local_product_name`, `local_product_cat`, `local_product_quantity_no_tracking`.

## Adapter của plugin

File: `includes/class-tgs-email-global-products.php`

Các hàm chính:

- `products_by_skus($skus)`: lấy sản phẩm global theo SKU.
- `products_by_ids($ids)`: lấy sản phẩm global theo ID, chỉ dùng khi dữ liệu báo cáo còn mang ID global.
- `name($product, $fallback)`: lấy tên sản phẩm global.
- `price_after_tax($product)`: lấy giá sau thuế từ catalog global.
- `enrich_rows_by_sku()`: bổ sung tên sản phẩm cho mảng báo cáo theo SKU.

## Các collector đã dùng global product

- `TGS_Collector_Shop_Sales`
  - Fallback HCL breakdown không join `local_product_name`.
  - Mapping HCL dùng `li.local_product_sku` để nối với `global_product_sci_mapping.sku`.

- `TGS_Collector_Shop_Gifts`
  - Quà tặng lấy SKU từ `local_ledger_item.local_product_sku`.
  - Tên và giá sau thuế lấy từ global product.

- `TGS_Collector_Shop_Max`
  - Dữ liệu tồn/max lấy từ rollup/config.
  - Tên sản phẩm được enrich lại từ global product.

- `TGS_Collector_Warehouse_MinMax`
  - Dữ liệu tồn/min/max/expiry lấy từ rollup/config.
  - Tên sản phẩm trong `below_min`, `above_max`, `stockout`, `near_expiry` được enrich lại từ global product.

## Quy ước phát triển

- Không query bảng `local_product_name` để lấy tên, giá, danh mục, barcode.
- Nếu collector có SKU, dùng `TGS_Email_Global_Products::products_by_skus()`.
- Nếu collector chỉ có ID global, dùng `products_by_ids()`.
- `local_product_sku` trong `local_ledger_item` chỉ là cột lưu SKU giao dịch/alias legacy, không phải nguồn catalog.
- Tài liệu API sản phẩm global xem thêm: `wp-content/plugins/tgs_shop_management/docs/global-product-api.md`.
