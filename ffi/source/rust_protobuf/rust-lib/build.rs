// rust-lib/build.rs
use std::io::Result;

fn main() -> Result<()> {
    let mut config = prost_build::Config::new();
    // Memaksa output ke folder src/generated agar bisa di-include secara relatif
    config.out_dir("src"); 
    config.compile_protos(&["../../../proto/api.proto"], &["../../../proto/"])?;
    Ok(())
}