import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { cn } from '@/lib/utils';
import type { AspectRatio } from '@/types';

interface ParamsDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  mode: 'single' | 'multiple';
  onModeChange: (mode: 'single' | 'multiple') => void;
  quality: '2k' | '4k';
  onQualityChange: (quality: '2k' | '4k') => void;
  aspectRatio: AspectRatio;
  onAspectRatioChange: (ratio: AspectRatio) => void;
  count?: number;
  onCountChange?: (count: number) => void;
}

const aspectRatios: { value: AspectRatio; label: string; w: number; h: number }[] = [
  { value: '1:1', label: '1:1', w: 1024, h: 1024 },
  { value: '2:3', label: '2:3', w: 768, h: 1152 },
  { value: '3:2', label: '3:2', w: 1152, h: 768 },
  { value: '3:4', label: '3:4', w: 768, h: 1024 },
  { value: '4:3', label: '4:3', w: 1024, h: 768 },
  { value: '9:16', label: '9:16', w: 576, h: 1024 },
  { value: '16:9', label: '16:9', w: 1024, h: 576 },
  { value: '9:21', label: '9:21', w: 448, h: 1024 },
  { value: '21:9', label: '21:9', w: 1024, h: 448 },
];

export default function ParamsDialog({
  open,
  onOpenChange,
  mode,
  onModeChange,
  quality,
  onQualityChange,
  aspectRatio,
  onAspectRatioChange,
  count = 1,
  onCountChange,
}: ParamsDialogProps) {
  const currentRatio = aspectRatios.find(r => r.value === aspectRatio) || aspectRatios[3];

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-[400px] p-0 gap-0">
        <DialogHeader className="px-5 py-4 border-b border-[#E5E5E5]">
          <DialogTitle className="text-base font-medium text-[#1A1A1A]">图片设置</DialogTitle>
        </DialogHeader>
        
        <div className="p-5 space-y-6">
          {/* Quality Selection */}
          <div>
            <label className="text-sm text-[#666666] mb-2 block">图像质量</label>
            <div className="flex p-1 bg-[#F5F5F5] rounded-lg">
              <button
                onClick={() => onQualityChange('2k')}
                className={cn(
                  "flex-1 py-2 text-sm rounded-md transition-all duration-200",
                  quality === '2k' 
                    ? "bg-white text-[#1A1A1A] shadow-sm" 
                    : "text-[#666666] hover:text-[#1A1A1A]"
                )}
              >
                高清 2K
              </button>
              <button
                onClick={() => onQualityChange('4k')}
                className={cn(
                  "flex-1 py-2 text-sm rounded-md transition-all duration-200",
                  quality === '4k' 
                    ? "bg-white text-[#1A1A1A] shadow-sm" 
                    : "text-[#666666] hover:text-[#1A1A1A]"
                )}
              >
                超清 4K
              </button>
            </div>
          </div>

          {/* Aspect Ratio */}
          <div>
            <label className="text-sm text-[#666666] mb-3 block">图片尺寸</label>
            <div className="grid grid-cols-5 gap-2">
              {aspectRatios.map((ratio) => (
                <button
                  key={ratio.value}
                  onClick={() => onAspectRatioChange(ratio.value)}
                  className={cn(
                    "flex flex-col items-center gap-1.5 p-2 rounded-lg border transition-all duration-200",
                    aspectRatio === ratio.value
                      ? "border-[#3B82F6] bg-[#F0F7FF]"
                      : "border-[#E5E5E5] hover:border-[#3B82F6]"
                  )}
                >
                  <div 
                    className={cn(
                      "border-2 rounded-sm",
                      aspectRatio === ratio.value ? "border-[#3B82F6]" : "border-[#999999]"
                    )}
                    style={{
                      width: ratio.value === '1:1' ? 16 : ratio.w > ratio.h ? 18 : 12,
                      height: ratio.value === '1:1' ? 16 : ratio.w > ratio.h ? 12 : 18,
                    }}
                  />
                  <span className={cn(
                    "text-[10px]",
                    aspectRatio === ratio.value ? "text-[#3B82F6]" : "text-[#666666]"
                  )}>{ratio.label}</span>
                </button>
              ))}
            </div>
            
            {/* Custom Size Input */}
            <div className="flex items-center gap-2 mt-3">
              <div className="flex items-center gap-2 flex-1">
                <span className="text-xs text-[#999999]">W</span>
                <input 
                  type="text" 
                  value={currentRatio.w}
                  readOnly
                  className="flex-1 h-8 px-3 text-sm bg-[#F5F5F5] rounded border border-[#E5E5E5] text-[#666666]"
                />
              </div>
              <span className="text-[#999999]">×</span>
              <div className="flex items-center gap-2 flex-1">
                <span className="text-xs text-[#999999]">H</span>
                <input 
                  type="text" 
                  value={currentRatio.h}
                  readOnly
                  className="flex-1 h-8 px-3 text-sm bg-[#F5F5F5] rounded border border-[#E5E5E5] text-[#666666]"
                />
              </div>
            </div>
          </div>

          {/* Image Count */}
          {onCountChange && (
            <div>
              <label className="text-sm text-[#666666] mb-2 block">图片张数</label>
              <div className="grid grid-cols-4 gap-2">
                {[1, 2, 3, 4].map((num) => (
                  <button
                    key={num}
                    onClick={() => onCountChange(num)}
                    className={cn(
                      "py-2 text-sm rounded-lg border transition-all duration-200",
                      count === num
                        ? "border-[#3B82F6] bg-[#F0F7FF] text-[#3B82F6]"
                        : "border-[#E5E5E5] hover:border-[#3B82F6] text-[#666666]"
                    )}
                  >
                    {num}张
                  </button>
                ))}
              </div>
            </div>
          )}
        </div>
      </DialogContent>
    </Dialog>
  );
}
